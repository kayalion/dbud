{extends file="app/index"}

{block name="content" append}
    {include file="dbud/repository.helper.actions"}
    {include file="dbud/repository.helper.branches" url="dbud.repository.integration"}
    {include file="dbud/repository.helper.tabs" section="integration"}
    
    <div class="btn-group">
        <a href="{url id="dbud.builder.add" parameters=["repository" => $repository->slug, "branch" => $branch]}" class="btn">{translate key="dbud.button.builder.add"}</a>
        <a href="{url id="dbud.repository.build" parameters=['repository' => $repository->slug, "branch" => $branch]}" class="btn{if !$builders} disabled{/if}">{translate key="dbud.button.build"}</a>
    </div>
    
    <p>&nbsp;</p>
        
    {if $builders}
        <table class="table table-striped">
            <tbody>        
            {foreach $builders as $builder}
                <tr>
                    <td>
                        <div class="btn-group pull-left" style="margin-right: 12px">
                            <a class="btn dropdown-toggle" data-toggle="dropdown" href="#">
                                <i class="icon icon-cog"></i>
                                <span class="caret"></span>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a href="{url id="dbud.builder.edit" parameters=["repository" => $repository->slug, "branch" => $branch, "builder" => $builder->slug]}">{translate key="dbud.button.builder.edit"}</a></li>
                                <li><a href="{url id="dbud.builder.delete" parameters=["repository" => $repository->slug, "branch" => $branch, "builder" => $builder->slug]}">{translate key="dbud.button.builder.delete"}</a></li>
                            </ul>
                        </div>                    
                        {$builder->name}
                    </td>
                    <td>
                        <div class="text-right">
                            <span class="label{if $builder->state == "ok"} label-success{/if}{if $builder->state =="working"} label-warning{/if}{if $builder->state == "error"} label-important{/if}">{translate key="dbud.state.`$builder->state`"}</span>
                            {if $builder->revision}
                                <a href="{url id="dbud.repository.commit" parameters=['repository' => $repository->slug, 'branch' => $branch, 'revision' => $builder->revision]}">{$builder->getFriendlyRevision()}</a>
                            {else}
                                ---
                            {/if}
                            {if $builder->dateBuilt}
                                <br />
                                {translate key="dbud.label.built" date=$builder->dateBuilt|date_format:"j M Y" time=$builder->dateBuilt|date_format:"H:i:s"}
                            {/if}
                        </div>
                    </td>
                </tr> 
            {/foreach}
            </tbody>
        </table>
    {else}
        <p>{translate key="dbud.label.builders.none"}</p>
    {/if}    
    </div>
{/block}