<ul class="nav nav-tabs">
    <li{if $section == 'history'} class="active"{/if}><a href="{url id="dbud.repository.commits" parameters=['repository' => $repository->slug, 'branch' => $branch]}">{translate key="dbud.button.commits"}</a></li>
    <li{if $section == 'files'} class="active"{/if}><a href="{url id="dbud.repository.files" parameters=['repository' => $repository->slug, 'branch' => $branch]}">{translate key="dbud.button.files"}</a></li>
    <li{if $section == 'integration'} class="active"{/if}><a href="{url id="dbud.repository.integration" parameters=['repository' => $repository->slug, 'branch' => $branch]}">{translate key="dbud.button.integration"}</a></li>        
    <li{if $section == 'deploy'} class="active"{/if}><a href="{url id="dbud.repository.deployment" parameters=['repository' => $repository->slug, 'branch' => $branch]}">{translate key="dbud.button.deployment"}</a></li>        
</ul>