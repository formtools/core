{include file="../../install/templates/install_header.tpl"}

    <h2>{$LANG.phrase_choose_components}</h2>

    <div id="component-table"></div>

    <script>
    React.render(FT.CompatibleComponentsContainer, document.getElementById('component-table'));
    </script>

{include file="../../install/templates/install_footer.tpl"}
