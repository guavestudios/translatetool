translatetool
=============
Default config:
```json
{
	"dbpath": "../../db/dblog.sqlite",
	"base": "/",
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
			"path": "{doc_root}/translation.json"
		}
	],
    "exports_combined": [
        {
            "adapter": "csv",
            "path": "{doc_root}/translation.csv"
        }
    ],
    "export_key_adapter": "yaml",
    "export_download": "exports_combined:0",
	"fields":[
		"key TEXT",
		"value TEXT",
		"parent_id INT",
		"language TEXT"
	]
}
```
Explanation:
- **dbpath**: Location of the SQLite DB relative to the translations.class.php File (because it is called from there).
- **base**: Determins what sub-path the installation is running on.
- **languages**: What languages can be translated.
- **users**: Users that can login to the GUI.
- **masters**: Masters will recieve any mutation that you do in your own application via API. _currently not supported anymore_
- **exports**: The export function will call each entry and use the given converter adapter to generate the given file at the given location.
{doc_root} is a placeholder for $_SERVER['DOCUMENT_ROOT']. {lang} is a placeholder for the language.
- **exports_combined**: Will call the export and export all languages at once, instead of one by one.
- **export_key_adapter**: Used by the Sublime Guave Translation plugin to insert a phrase and return the key in the syntax of the given adapter.
- **export_download**: Defines what adapter will be used to deliver the downloadable file. Syntax: {configuration key}:{array index}.
- **fields**: The fields that are created when the database is created from scratch. You usually will not need to change these fields.

Inline Widget
====
```html
<script type='text/javascript'>
	window.translateToolConfig = {
		language: "{{ app.request.locale }}",
		instanceUrl: "http://translatetool.local"
	};
	(function (d, t) {
		var tt = d.createElement(t), s = d.getElementsByTagName(t)[0];
		tt.async=true;
		tt.type = 'text/javascript';
		tt.src = '//translatetool.local/gui/js/widget.js';
		s.parentNode.insertBefore(tt, s);
	})(document, 'script');
</script>
```
```yml
# /app/config.yml
twig:
    autoescape: false
```