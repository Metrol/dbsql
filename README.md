# Metrol\DBSql
## Writing SQL syntax with PHP

Another library for writing SQL?  Did the world really need another one of these? Well, I thought it did, so I wrote it up.

Other libraries I've seen tackle this problem either were twice to clever for what was needed, and tended to get brittle when the SQL got clever.  My approach was to keep the code simple, and hopefully handle things properly when the SQL gets clever.

## Features
* Simple syntax.
* Automatically quotes Field and Table names.
* Ability to turn off automatic quoting on the fly.
* Automatically bind values on the fly, or use named binding for PDO.
* Ability to create SQL without worrying about the order of the commands.
* Select, Insert, Update, Delete, With, and Union queries supported.
* Ability to clear out any section on the fly, then add back into it.
* Support for creating sub-selects, and to set criteria with sub-selects.
* Merging Select statement into others, WITH, and Unions also merges bindings.

At the moment it only supports `PostgreSQL`, with work about to start on MySQL.

## What this is not
This is not an SQL abstraction tool to allow for switching between different databases.  There are other tools that do this already.  In order to go down this road you have to pick some subset of commands that either all the databases support, or that can be faked in code.  Instead, this library is for those of us who want to use all the tools that our chosen database has to offer.

With that being said, SQL can be a complex language with lots of syntax challenges.  This library does not pretend to meet all those challenges.  If you're writing really complex SQL there may never be a tool that "Just Works".  My hope is that what is here can hit that sweet spot of handling the vast majority of SQL needs while doing so in a reasonably elegant way.

## Examples
So what all does this look like?  A fair question, and too far into this to not show this off just a bit.

```php
$select = new PostgreSQL\Select;
$select->from('Somewhere sw')
       ->where('id = ?', [23]);

print $select->output();
```

Produces SQL that looks like...

```sql
SELECT
    *
FROM
    "Somewhere" "sw"
WHERE
    "id" = :_56ea34645f1c7_
```

Then we also get the binding, all ready to go into a PDO execute statement.  To actually run this against your database it would look like...

```php
$statement = $pdo->prepare($select->output());
$statement->execute($select->getBindings());
```



