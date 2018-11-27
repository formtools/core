{ft_include file='header.tpl'}

<table cellpadding="0" cellspacing="0">
    <tr>
        <td width="45"><img src="{$images_url}/icon_modules.gif" width="34" height="34"/></td>
        <td class="title">{$LANG.word_update}</td>
    </tr>
</table>

<div id="manage-components"></div>

<script>
	ReactDOM.render(FT.ManageModulesContainer, document.getElementById('manage-components'));
</script>

{ft_include file='footer.tpl'}
