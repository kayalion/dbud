<div class="pull-right">
    <div class="btn-group pull-right">
        <a class="btn dropdown-toggle" data-toggle="dropdown" href="#">
            <i class="icon icon-cog"></i>
            <span class="caret"></span>
        </a>
        <ul class="dropdown-menu">
            <li><a href="{url id="dbud.repository.activity" parameters=['repository' => $repository->slug]}">{translate key="dbud.button.activity"}</a></li>
            <li class="divider"></li>
            <li><a href="{url id="dbud.repository.update" parameters=['repository' => $repository->slug]}">{translate key="dbud.button.repository.update"}</a></li>
            <li><a href="{url id="dbud.repository.edit" parameters=['repository' => $repository->slug]}">{translate key="dbud.button.repository.edit"}</a></li>
            <li><a href="{url id="dbud.repository.delete" parameters=['repository' => $repository->slug]}">{translate key="dbud.button.repository.delete"}</a></li>
        </ul>
    </div>
    <p class="muted pull-right" style="padding: 5px 12px 0 0">{$repository->repository}</p>
</div>