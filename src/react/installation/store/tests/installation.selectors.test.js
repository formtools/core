import sinon from 'sinon';
import * as selectors from '../installation.selectors';
import { generalUtils } from '../../../utils';

describe('getConfigFileContent', () => {

	const state = {
		installation: {
			dbSettings: {
				dbHostname: 'dbHostname',
				dbName: 'dbName',
				dbPort: 'dbPort',
				dbUsername: 'dbUsername',
				dbPassword: 'dbPassword',
				dbTablePrefix: 'dbTablePrefix'
			},
			folderSettings: {
				useCustomCacheFolder: false
			}
		},
		constants: {
			rootDir: '/Applications/FormTools'
		}
	};

	beforeEach(() => {
		sinon.stub(generalUtils, 'getCurrentUrl').returns('http://localhost/install/#/step4');
	});

	afterEach(() => {
		generalUtils.getCurrentUrl.restore();
	});

	it('generates the settings file as expected', () => {
		expect(selectors.getConfigFileContent(state)).toEqual(`<?php

// main program paths - no trailing slashes!
$g_root_url = "http://localhost";
$g_root_dir = "/Applications/FormTools";

// database settings
$g_db_hostname = "dbHostname";
$g_db_port = "dbPort";
$g_db_name = "dbName";
$g_db_username = "dbUsername";
$g_db_password = "dbPassword";
$g_table_prefix = "dbTablePrefix";

?>
`);
	});

	it('escapes $ chars in usernames and password', () => {
		const newState = generalUtils.deepCopy(state);
		newState.installation.dbSettings.dbUsername = 'db$Username';
		newState.installation.dbSettings.dbPassword = 'db$Pass$word';

		expect(selectors.getConfigFileContent(newState)).toEqual(`<?php

// main program paths - no trailing slashes!
$g_root_url = "http://localhost";
$g_root_dir = "/Applications/FormTools";

// database settings
$g_db_hostname = "dbHostname";
$g_db_port = "dbPort";
$g_db_name = "dbName";
$g_db_username = "db\\$Username";
$g_db_password = "db\\$Pass\\$word";
$g_table_prefix = "dbTablePrefix";

?>
`);
	});

	it('escapes backslash characters in root dir for Windows machines', () => {
		const newState = generalUtils.deepCopy(state);
		newState.constants.rootDir = 'c:\\location\\to\\formtools';

		// the four slashes look insane, but 2 of them are just for JS. In the page and in the generated file,
		// there are two slashes. This is only for Windows and correct.
		expect(selectors.getConfigFileContent(newState)).toEqual(`<?php

// main program paths - no trailing slashes!
$g_root_url = "http://localhost";
$g_root_dir = "c:\\\\location\\\\to\\\\formtools";

// database settings
$g_db_hostname = "dbHostname";
$g_db_port = "dbPort";
$g_db_name = "dbName";
$g_db_username = "dbUsername";
$g_db_password = "dbPassword";
$g_table_prefix = "dbTablePrefix";

?>
`);
	});

	it('does not include the custom cache folder if it is the same as the default one', () => {
		const newState = generalUtils.deepCopy(state);
		newState.installation.folderSettings.useCustomCacheFolder = true;
		newState.installation.folderSettings.defaultCacheFolder = '/Applications/folder';
		newState.installation.folderSettings.customCacheFolder = '/Applications/folder';

		expect(selectors.getConfigFileContent(newState)).toEqual(`<?php

// main program paths - no trailing slashes!
$g_root_url = "http://localhost";
$g_root_dir = "/Applications/FormTools";

// database settings
$g_db_hostname = "dbHostname";
$g_db_port = "dbPort";
$g_db_name = "dbName";
$g_db_username = "dbUsername";
$g_db_password = "dbPassword";
$g_table_prefix = "dbTablePrefix";

?>
`);
	});

	it('include the custom cache folder if it is different from the default one', () => {
		const newState = generalUtils.deepCopy(state);
		newState.installation.folderSettings.useCustomCacheFolder = true;
		newState.installation.folderSettings.defaultCacheFolder = '/Applications/folder';
		newState.installation.folderSettings.customCacheFolder = '/Applications/newfolder';

		expect(selectors.getConfigFileContent(newState)).toEqual(`<?php

// main program paths - no trailing slashes!
$g_root_url = "http://localhost";
$g_root_dir = "/Applications/FormTools";

// database settings
$g_db_hostname = "dbHostname";
$g_db_port = "dbPort";
$g_db_name = "dbName";
$g_db_username = "dbUsername";
$g_db_password = "dbPassword";
$g_table_prefix = "dbTablePrefix";
$g_custom_cache_folder = "/Applications/newfolder";

?>
`);
	});

	it('escapes backslash characters in custom cache dir for Windows machines', () => {
		const newState = generalUtils.deepCopy(state);
		newState.installation.folderSettings.useCustomCacheFolder = true;
		newState.installation.folderSettings.defaultCacheFolder = '/Applications/folder';
		newState.installation.folderSettings.customCacheFolder = 'C:\\custom\\folder';

		expect(selectors.getConfigFileContent(newState)).toEqual(`<?php

// main program paths - no trailing slashes!
$g_root_url = "http://localhost";
$g_root_dir = "/Applications/FormTools";

// database settings
$g_db_hostname = "dbHostname";
$g_db_port = "dbPort";
$g_db_name = "dbName";
$g_db_username = "dbUsername";
$g_db_password = "dbPassword";
$g_table_prefix = "dbTablePrefix";
$g_custom_cache_folder = "C:\\\\custom\\\\folder";

?>
`);
	});

});