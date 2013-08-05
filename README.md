translatetool
=============
Default config:
```json
{
	"dbpath": "../db/dblog.sqlite",
	"languages": ["de", "en"],
	"users":[
		{
			"username": "admin",
			"passwd": "admin",
			"roles": [
				"admin"
			]
		},
		{
			"username": "user",
			"passwd": "user",
			"roles": [
				"client"
			]
		}
	],
	"masters": [
		"http://translatetool-master.local"
	],
	"exports": [
		{
			"adapter": "json",
			"path": "{doc_root}/translation.{lang}.json"
		},
		{
			"adapter": "yaml",
			"path": "{doc_root}/translation.{lang}.yml"
		}
	],
	"fields":[
		"key TEXT",
		"value TEXT",
		"parent_id INT"
	]
}
```
Explanation:

dbpath: Location of the SQLite DB relative to the translations.class.php File (because it is called from there).

users: Users that can login to the GUI.

masters: Masters will recieve any mutation that you do in your own application via API.

exports: The export function will call each entry and use the given converter adapter to generate the given file at the given location. {doc_root} is a placeholder for $_SERVER['DOCUMENT_ROOT'].

fields: The fields that are created when the database is created from scratch. You usually will not need to change these fields.
