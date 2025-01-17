# Advanced usage

## Configuration via `QROptions`

The [`QROptions`](https://github.com/chillerlan/php-qrcode/blob/main/src/QROptions.php) class is a container based on [chillerlan/php-settings-container](https://github.com/chillerlan/php-settings-container) that behaves similar to a [`\stdClass`](https://www.php.net/manual/class.stdclass) object, but with fixed properties.

```php
$options = new QROptions;

// set some values
$options->version = 7;     // property "version" exists
$options->foo     = 'bar'; // property "foo" does not exist (and will not be created)

// retrieve values
var_dump($options->version); // -> 7
var_dump($options->foo);     // -> null (no error will be thrown)
```


### Supply an `iterable` of options

The constructor takes an `iterable` of `$key => $value` pairs. For each setting an optional setter will be called if present.

```php
$myOptions = [
	'version'    => 5,
	'outputType' => QROutputInterface::GDIMAGE_PNG,
	'eccLevel'   => EccLevel::M,
];

$options = new QROptions($myOptions);
```

You can also set an `iterable` of options on an existing QROptions instance:

```php
$options->fromIterable($myOptions);
```


### Load and save JSON

The settings can be saved to and loaded from JSON, e.g. to store them in a database:

```php
$json = $options->toJSON(JSON_THROW_ON_ERROR);
// via JsonSerializable interface
$json = json_encode($options, JSON_THROW_ON_ERROR);
// via __toString()
$json = (string)$options;

$options = (new QROptions)->fromJSON($json);
// on an existing instance - properties will be overwriten
$options->fromJSON($json);
```

### Extending the `QROptions` class

In case you need additional settings for your output module, just extend `QROptions`...

```php
class MyCustomOptions extends QROptions{
	protected string $myParam = 'defaultValue';
	// ...
}
```
...or use the [`SettingsContainerInterface`](https://github.com/chillerlan/php-settings-container/blob/main/src/SettingsContainerInterface.php), which is the more flexible approach.

```php
trait MyCustomOptionsTrait{
	protected string $myParam = 'defaultValue';
	// ...

	// an optional magic setter, named "set_" + property name
	protected function set_myParam(string $myParam):void{
		$this->myParam = trim($myParam);
	}

	// an optional magic getter, named "get_" + property name
	protected function get_myParam():string{
		return strtoupper($this->myParam);
	}
}

class MyCustomOptions extends SettingsContainerAbstract{
	use QROptionsTrait, MyCustomOptionsTrait;
}

// set the options
$myCustomOptions = new MyCustomOptions;
$myCustomOptions->myParam = 'whatever value';
```

Extend the `SettingsContainerInterface` on-the-fly:

```php
$myOptions = [
	'myParam' => 'whatever value',
	// ...
];

$myCustomOptions = new class($myOptions) extends SettingsContainerAbstract{
	use QROptionsTrait, MyCustomOptionsTrait;
};
```


## `QRCode` methods

Aside of invoking a `QRCode` instance with an optional `QROptions` object as parameter, you can also set the options instance after invocation.
After invocation of the `QROptions` instance, values can be set without calling `QRCode::setOptions()` again (instance is backreferenced), however, this may create side effects.

```php
// instance will be invoked with default settings
$qrcode  = new QRCode;

// set options after QRCode invocation
$options = new QROptions;

$qrcode->setOptions($options);
```


### Save to file

Save the QR Code output to `/path/to/qrcode.svg`:

```php
$options->outputBase64 = false;
$options->cachefile    = '/path/to/qrcode.svg';

$qrcode->render($data);
```

Alternatively, you can specify an output path, which will override the `QROptions::$cachefile` setting:

```php
$qrcode->render($data, '/other/path/to/qrcode.svg');

printf('<img src="%s" alt="QR Code" />', '/other/path/to/qrcode.svg');
```


### Render a `QRMatrix` instance

You can render a [`QRMatrix`](https://github.com/chillerlan/php-qrcode/blob/main/src/Data/QRMatrix.php) instance directly:

```php
// a matrix from the current data segments
$matrix = $qrcode->getQRMatrix();
// from the QR Code reader
$matrix = $readerResult->getQRMatrix();
// manually invoked
$matrix = (new QRMatrix(new Version(7), new EccLevel(EccLevel::M)))->initFunctionalPatterns();

$output = $qrcode->renderMatrix($matrix);
// save to file
$qrcode->renderMatrix($matrix, '/path/to/qrcode.svg');
```

Manual invocation of a `QROutputInterface` to render a `QRMatrix` instance:

```php
class MyCustomOutput extends QRMarkupSVG{
	// ...
}

$qrOutputInterface = new MyCustomOutput($options, $matrix);

$output = $qrOutputInterface->dump()
// render to file
$qrOutputInterface->dump('/path/to/qrcode.svg');
```


### Mixed mode

Mixed mode QR Codes can be generated by adding several data segments:

```php
// make sure to set a proper internal encoding character set
// ideally, this should be set in php.ini internal_encoding,
// default_charset or mbstring.internal_encoding
mb_internal_encoding('UTF-8');

// clear any existing data segments
$qrcode->clearSegments();

$qrcode
	->addNumericSegment($numericData)
	->addAlphaNumSegment($alphaNumData)
	->addKanjiSegment($kanjiData)
	->addHanziSegment($hanziData)
	->addByteSegment($binaryData)
	->addEciSegment(ECICharset::GB18030, $encodedEciData)
;

$output = $qrcode->render();
// render to file
$qrcode->render(null, '/path/to/qrcode.svg');
```

The [`QRDataModeInterface`](https://github.com/chillerlan/php-qrcode/blob/main/src/Data/QRDataModeInterface.php) offers the `validateString()` method (implemended for `AlphaNum`, `Byte`, `Hanzi`, `Kanji` and `Number`).
This method is used internally when a data mode is invoked, but it can come in handy if you need to check input data beforehand.

```php
if(!Hanzi::validateString($data)){
	throw new Exception('invalid GB2312 data');
}

$qrcode->addHanziSegment($data);
```


### QR Code reader

In some cases it might be necessary to increase the contrast of a QR Code image:

```php
$options->readerUseImagickIfAvailable = true;
$options->readerIncreaseContrast      = true;
$options->readerGrayscale             = true;

$result = (new QRCode($options))->readFromFile('path/to/qrcode.png');
```

The `QRMatrix` object from the [`DecoderResult`](https://github.com/chillerlan/php-qrcode/blob/main/src/Decoder/DecoderResult.php) can be reused:

```php
$matrix = $result->getQRMatrix();

// ...matrix modification...

$output = (new QRCode($options))->renderMatrix($matrix);

// ...output
```
