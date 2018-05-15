/*
Make request to global/react-init.php

That file :
	- checks sessions to find out if AUTH or not and what language is selected.
	- always returns the same object structure, content determined by sessions.
	- returns:
		{
			authenticated: true | false,
			userInfo: {},
			i18n: {},
			constants: {}
		}

Either fail after 30 seconds, or populate store with this data.
*/
