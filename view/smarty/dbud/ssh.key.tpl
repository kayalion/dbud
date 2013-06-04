{extends file="app/index"}

{block name="content" append}

    <p>{translate key="dbud.label.ssh.key"}</p>

    <div class="row-fluid">
        <textarea rows="5" class="span12">{$publicKey}</textarea>
    </div>

{/block}