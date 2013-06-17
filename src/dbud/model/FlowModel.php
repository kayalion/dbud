<?php

namespace dbud\model;

use dbud\model\data\BuilderData;
use dbud\model\data\FlowData;
use dbud\model\data\ProjectData;
use dbud\model\data\ServerData;
use dbud\model\data\RepositoryData;

use dbud\Module;

use zibo\library\orm\model\GenericModel;

/**
 * Flow model
 */
class FlowModel extends GenericModel {

    /**
     * Name of this model
     * @var string
     */
    const NAME = 'DbudFlow';

    /**
     * Gets the flow of a project
     * @param string $projectId
     * @param array $flow
     * @param array $connections
     * @return null
     */
    public function getFlowForProject($projectId, array &$flow, array &$connections) {
        $query = $this->createQuery();
        $query->setRecursiveDepth(0);
        $query->addCondition('{project} = %1%', $projectId);

        $flow = $query->query();

        foreach ($flow as $data) {
            $connections[$data->dataType][$data->dataId] = $data->previous;
        }
    }

    /**
     * Saves the flow
     * @param dbud\model\data\ProjectData $project
     * @param array $data
     * @return null
     */
    public function saveFlowForProject(ProjectData $project, array $data) {
        $toDelete = $project->flow;
        $toSave = array();

        $transactionStarted = $this->beginTransaction();
        try {
            // save the data of the flow
            foreach ($data as $flowIndex => $flow) {
                if ($flow['id'] == 'repository-0') {
                    continue;
                }

                list($dataType, $dataId) = explode('-', $flow['id'], 2);
                $dataType = 'Dbud' . ucfirst($dataType);

                $data[$flowIndex]['dataType'] = $dataType;
                $data[$flowIndex]['dataId'] = $dataId;

                $query = $this->createQuery();
                $query->setRecursiveDepth(0);
                $query->addCondition('{project} = %1%', $project->id);
                $query->addCondition('{dataType} = %1% AND {dataId} = %2%', $dataType, $dataId);

                $flowData = $query->queryFirst();
                if ($flowData) {
                    unset($toDelete[$flowData->id]);
                } else {
                    $flowData = $this->createData();
                    $flowData->project = $project->id;
                    $flowData->dataType = $dataType;
                    $flowData->dataId = $dataId;
                }

                $flowData->positionTop = $flow['top'];
                $flowData->positionLeft = $flow['left'];

                $this->save($flowData);

                $toSave[] = $flowData;
            }

            // link the flow data and delete the unnecessairy data
            foreach ($toSave as $flowData) {
                $previous = 0;

                foreach ($data as $flow) {
                    if ($flow['id'] == 'repository-0' || $flow['dataType'] != $flowData->dataType || $flow['dataId'] != $flowData->dataId) {
                        continue;
                    }

                    if ($flow['previous'] && $flow['previous'] != 'null') {
                        list($dataType, $dataId) = explode('-', $flow['previous'], 2);
                        $dataType = 'Dbud' . ucfirst($dataType);

                        foreach ($toSave as $saveData) {
                            if ($saveData->dataType != $dataType || $saveData->dataId != $dataId) {
                                continue;
                            }

                            $previous = $saveData->id;

                            break;
                        }
                    }

                    break;
                }

                $flowData->previous = $previous;

                $this->save($flowData, 'previous');
            }

            if ($toDelete) {
                $this->delete($toDelete);
            }

            $this->commitTransaction($transactionStarted);
        } catch (Exception $exception) {
            $this->rollbackTransaction($transactionStarted);

            throw $exception;
        }
    }

    /**
     * Sets the project flow in motion
     * @param dbud\model\data\RepositoryData $repository
     * @return null
     */
    public function onRepositoryUpdate(RepositoryData $repository, array $revisions) {
        $query = $this->createQuery();
        $query->setRecursiveDepth(0);
        $query->addJoin('INNER', 'DbudProjectDbudRepository', 'pr', '{pr.dbudProject} = {project}');
        $query->addCondition('{pr.dbudRepository} = %1%', $repository->id);
        $query->addCondition('{previous} IS NULL');

        $flowResult = $query->query();
        foreach ($flowResult as $flowData) {
            $this->queueFlow($flowData, $revisions);
        }
    }

    /**
     * Queues the next flow after the provided build
     * @param dbud\model\data\BuilderData $builder
     * @return null
     */
    public function onBuild(BuilderData $builder) {
        $query = $this->createQuery();
        $query->setRecursiveDepth(0);
        $query->addCondition('{dataType} = %1% AND {dataId} = %2%', 'DbudBuilder', $builder->id);

        $result = $query->query();
        foreach ($result as $flow) {
            $query = $this->createQuery();
            $query->addCondition('{previous} = %1%', $flow->id);

            $flowResult = $query->query();
            foreach ($flowResult as $flowData) {
                $this->queueFlow($flowData, $builder->revision);
            }
        }
    }

    /**
     * Queues the next flow after the provided deploy
     * @param dbud\model\data\ServerData $server
     * @return null
     */
    public function onDeploy(ServerData $server) {
        $query = $this->createQuery();
        $query->setRecursiveDepth(0);
        $query->addCondition('{dataType} = %1% AND {dataId} = %2%', 'DbudServer', $server->id);

        $result = $query->query();
        foreach ($result as $flow) {
            $query = $this->createQuery();
            $query->addCondition('{previous} = %1%', $flow->id);

            $flowResult = $query->query();
            foreach ($flowResult as $flowData) {
                $this->queueFlow($flowData, $server->revision);
            }
        }
    }

    /**
     * Queues flow data
     * @param dbud\model\data\FlowData $flow
     * @return null
     */
    protected function queueFlow(FlowData $flow, $revision = null) {
        $activityModel = $this->orm->getDbudActivityModel();

        switch ($flow->dataType) {
            case 'DbudBuilder':
                $builderModel = $this->orm->getDbudBuilderModel();

                $builder = $builderModel->getById($flow->dataId);
                if (!$builder) {
                    break;
                }

                if (is_array($revision)) {
                    if (isset($revision[$builder->branch])) {
                        $revision = $revision[$builder->branch];
                    } else {
                        $revision = null;
                    }
                }

                if ($revision && $builder->revision == $revision) {
                    break;
                }

                $activityModel->queueBuild($builder);

                break;
            case 'DbudServer':
                $serverModel = $this->orm->getDbudServerModel();

                $server = $serverModel->getById($flow->dataId);
                if (!$server) {
                    break;
                }

                if (is_array($revision)) {
                    if (isset($revision[$server->branch])) {
                        $revision = $revision[$server->branch];
                    } else {
                        $revision = null;
                    }
                }

                if ($revision && $server->revision == $revision && $server->state == Module::STATE_OK) {
                    break;
                }

                $activityModel->queueDeploy($server);

                break;
        }
    }

}