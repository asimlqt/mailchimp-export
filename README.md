# mailchimp-export
A simple library to export Mailchimp lists

Requires PHP >= 7

## Installation
Use the following composer command to install
```
composer require asimlqt/mailchimp-export
```

## Example
The following is an example that exports a mailchimp list and saves it to a csv file.

```php
<?php

require_once './vendor/autoload.php';

use Asimlqt\MailchimpExport\MailchimpException;
use Asimlqt\MailchimpExport\ListExport;
use Asimlqt\MailchimpExport\Writer\CsvWriter;

$writer = new CsvWriter(new SplFileObject("/my/path/list.csv", "w"));

try {
    $exp = new ListExport($apiKey, $listId, $writer);
    $exp->run();
} catch (MailchimpException $e) {
    echo $e->getMessage();    
}
```

## Writers

There are currently 2 writers that are provided by default:

### CSV Writer

See example above.


### Database Writer

**CAUTION: The table specified will be truncated before being written to!**

This requires a little more configuration than the csv writer.

The constructor has the following definition:

```php
public function __construct(PDO $pdo, string $table, array $mapping)
```

`$pdo` is just a standard php pdo connection object.

`$table` is the name of the table that you want to write to

`$mapping` is an array that maps mailchimp fields to database columns. The key of the array is the name of the mailchimp field and the value is the database column.

Example of using the database writer:

```php
<?php

require_once './vendor/autoload.php';

use Asimlqt\MailchimpExport\MailchimpException;
use Asimlqt\MailchimpExport\ListExport;
use Asimlqt\MailchimpExport\Writer\DatabaseWriter;

$mapping = [
    'Email Address' => 'email',
    'First Name' => 'firstname',
    'Last Name' => 'lastname',
];

$pdo = new PDO('mysql:dbname=mailchimp;host=127.0.0.1', 'user', 'password');
$writer = new DatabaseWriter($pdo, 'subscribers', $mapping);

try {
    $exp = new ListExport($apiKey, $listId, $writer);
    $exp->run();
} catch (MailchimpException $e) {
    echo $e->getMessage();
}
```

In the above example 'Email Address', 'First Name' and 'Last Name' are names of mailchimp fields and 'email', 'firstname' and 'lastname' are database columns they will be written to. The `CsvWriter` just writes everything to a csv file as is hence it doesn't require a mapping.

The data is inserted into the database in batches. The default size is 100 rows. You can set that to a different value if you wish using `setBatchSize()` method of `DatabaseWriter` 

### Custom Writer

If you need to write the data elsewhere then simply extend the `Writer` interface which only has one method and pass it to the `ListExport` constructor.

```php
interface Writer
{
    public function write(array $data);
}
```