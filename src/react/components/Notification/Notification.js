import React from 'react';

const Notification = (msg, msgType) => {
	//const className = msgType === 'error' ? styles.

	return (
		<div>
			{msg}
		</div>
	);
};


// //<![CDATA[
// var g = {literal}{}{/literal};
// 	g.root_url = "{$g_root_url|default:""}";
// 	g.error_colours = ["ffbfbf", "ffeded"];
// 	g.notify_colours = ["c6e2ff", "f2f8ff"];
// 	//]]>
//
//
const ErrorMsg = (msg) => <Notification msg={msg} type="error" />;
const SuccessMsg = (msg) => <Notification msg={msg} type="success" />;