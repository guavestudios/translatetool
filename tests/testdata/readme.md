# Testcases

01 No failures - Import OK

### ERRORS
02
03 folderIsFileErrors   - Folder is File - new Folder cannot have same name as file in same subfolder
04 Specialchar - Import OK
05 Multilinehtml - Import OK
06 tooManyLangErrors    - More langs in csv than in config
07 duplicateErrors      - Duplicate keys
08 invalidFormatErrors  - Invalid key format - space in key-name
09 invalidFormatErrors  - Invalid key format - no dot in key-name
10 invalidFormatErrors  - Invalid key format - specialchars in key-name
11 emptyValueErrors     - Empty values in csv
12 inCsvNotInDbErrors   - Value only in csv not in DB

### WARNING
20 inDbNotInCsvWarning        - Value in db but not in csv
21 moreLangInConfigWarning    - More langs in config defined than in csv provided
