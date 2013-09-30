json-sql-import
===============

Small PHP command line tool to import JSON data into database tables using transformation rules.

Usage
-----

```php
php json-import.php <options>
```

Omit the options to print the help / usage notice.

Example
-------

Have a look at the [demo ruleset](https://github.com/andywer/json-sql-import/blob/master/rulesets/demo/test.json).

You would use this ruleset using `php json-import.php import demo/test path/to/data.json`. An appropriate JSON data file would have to look like this:

```json
[
  {
    "somevar": "hello world",
    "somearray": [
      { "name": "somenumber", "value": 123.45 },
      ...
    ]
  },
  
  ...
]
```

License
-------

This software is released under the MIT license. See [license](https://raw.github.com/andywer/json-sql-import/master/LICENSE).

