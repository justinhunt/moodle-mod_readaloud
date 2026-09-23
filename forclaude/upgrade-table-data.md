Mod_ReadAloud

When Moodle encounters a plugin in its file system that has a higher version than previously, it runs an upgrade script. The file in each plugin that tells Moodle what to do in the case of an upgrade is in [plugin]/db/upgrade.php. Usually these tasks are database structure and content related. If the new version is several versions ahead of the previous version, in most cases it will run through several steps in the upgrade.php file one or more for each version.

In the case of ReadAloud there was a large update in version (2026030603) and there are several steps for adding database fields and setting their values for ReadAlouds whose versions are older than this. In the plugins upgrade.php you can see these. 

There is an issue for existing users who may arrive on a version 2026030603 or higher, but have not run the upgrade script. The expected workflow is that users will overwrite/upgrade their existing ReadAloud plugin on the same site. Then Moodle will detect the version increase, and run the upgrade script. 

But in the case where a user creates a new up-to-date Moodle site with the latest version of ReadAloud and then restores their existing ReadAloud activities from a course backup file, the upgrade script does not run. Because it only runs when the installed ReadAloud version changes.  Though the database schema is correct, some important updates such as the enabling of optional steps (line 946 of upgrade.php) are made in the upgrade script. These are missed when the user restores without upgrading. So the data in the tables is not as expected. In this state, which is undesireable, the user must manually edit each activity settings before they can use it.

Can you investigate the problem and advise what should be done, or advise if I have misunderstood something?  