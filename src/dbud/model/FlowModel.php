<?php

namespace dbud\model;

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
     * @param ProjectData $project
     * @param array $data
     * @return null
     */
    public function saveFlowForProject($project, array $data) {
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

}