import React from 'react';


const Navigation = ({ i18n }) => (
	<div id="nav_items">
		<div className="{if $step == 1}nav_current{else}nav_visited{/if}">
			1 <span className="delim">-</span> {i18N.word_welcome}
		</div>
		<div className="{if $step == 2}nav_current{elseif $step < 2}nav_remaining{else}nav_visited{/if}">
			2 <span className="delim">-</span> {i18n.phrase_system_check}
		</div>
		<div className="{if $step == 3}nav_current{elseif $step < 3}nav_remaining{else}nav_visited{/if}">
			3 <span className="delim">-</span> {i18n.phrase_create_database_tables}
		</div>
		<div className="{if $step == 4}nav_current{elseif $step < 4}nav_remaining{else}nav_visited{/if}">
			4 <span className="delim">-</span> {i18n.phrase_create_config_file}
		</div>
		<div className="{if $step == 5}nav_current{elseif $step < 5}nav_remaining{else}nav_visited{/if}">
			5 <span className="delim">-</span> {i18n.phrase_create_admin_account}
		</div>
		<div className="{if $step == 6}nav_current{elseif $step < 6}nav_remaining{else}nav_visited{/if}">
			6 <span className="delim">-</span> {i18n.phrase_clean_up}
		</div>
	</div>
);

export default Navigation;
