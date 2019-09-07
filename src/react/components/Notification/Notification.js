import React from 'react';

const Notification = (msg, msgType) => {
	//const className = msgType === 'error' ? styles.

	return (
		<div>
			{msg}
		</div>
	);
};


const ErrorMsg = (msg) => <Notification msg={msg} type="error" />;
const SuccessMsg = (msg) => <Notification msg={msg} type="success" />;