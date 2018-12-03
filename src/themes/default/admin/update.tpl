{ft_include file='header.tpl'}

<table cellpadding="0" cellspacing="0">
    <tr>
        <td width="45"><img src="{$images_url}/icon_modules.gif" width="34" height="34"/></td>
        <td class="title">Components</td>
    </tr>
</table>


Show
<input type="button" value="Modules" />
<input type="button" value="Themes" />
<input type="button" value="API" />
<input type="button" value="Core" />

|

<input type="checkbox" /> Show uninstalled components

<br />
<br />


<div id="manage-components"></div>


<script>
	ReactDOM.render(FT.ManageModulesContainer, document.getElementById('manage-components'));
</script>

{ft_include file='footer.tpl'}
