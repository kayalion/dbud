<div class="btn-group" style="float: left; margin-right: 12px;">
    <a class="btn dropdown-toggle" data-toggle="dropdown" href="#">
        <span class="muted">branch:</span> <strong>{$branch}</strong>
        <span class="caret"></span>
    </a>
    <ul class="dropdown-menu">
        {foreach $branches as $b}
        <li><a href="{url id=$url parameters=['repository' => $repository->slug, 'branch' => $b]}">{$b}</a></li>
        {/foreach}
    </ul>
</div>