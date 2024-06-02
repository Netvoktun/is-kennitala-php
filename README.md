# Icelandic Kennitala Validation and Utilities

This PHP code provides a set of functions for validating, formatting, cleaning, and identifying Icelandic Kennitala (national identification number).

## Features

- Validate Kennitala: Check if a given string is a technically valid Kennitala.
- Format Kennitala: Clean up and format a Kennitala string with a separator.
- Clean Kennitala: Remove spaces, dashes, and non-digit characters from a Kennitala string.
- Get Birth Date: Extract the birth date or founding date from a Kennitala string.
- Identify Kennitala Type: Determine if a Kennitala belongs to a person, company, or is a temporary "kerfiskennitala".
- Generate Kennitala: Generate a technically valid Kennitala (possibly a real one).

## Usage

### Validating a Kennitala

To check if a string is a valid Kennitala, use the `isValidKennitala` function:

```php
$kennitala = '1234567890';
if (isValidKennitala($kennitala)) {
    echo 'Valid Kennitala';
} else {
    echo 'Invalid Kennitala';
}
```

### Formatting a Kennitala

To format a Kennitala string with a separator (default is '-'), use the `formatKennitala` function:

```php
$kennitala = '1234567890';
$formattedKennitala = formatKennitala($kennitala);
echo $formattedKennitala; // Output: 123456-7890
```

### Cleaning a Kennitala

To clean up a Kennitala string by removing spaces, dashes, and non-digit characters, use the `cleanKennitalaCareful` or `cleanKennitalaAggressive` functions:

```php
$kennitala = '123456 - 7890';
$cleanedKennitala = cleanKennitalaCareful($kennitala);
echo $cleanedKennitala; // Output: 1234567890
```

### Getting Birth Date from a Kennitala

To extract the birth date or founding date from a Kennitala string, use the `getKennitalaBirthDate` function:

```php
$kennitala = '1234567890';
$birthDate = getKennitalaBirthDate($kennitala);
if ($birthDate) {
    echo $birthDate->format('Y-m-d'); // Output: YYYY-MM-DD
} else {
    echo 'Invalid Kennitala or birth date';
}
```

### Identifying Kennitala Type

To determine the type of a Kennitala (person, company, or temporary), use the `isPersonKennitala`, `isCompanyKennitala`, or `isTempKennitala` functions:

```php
$kennitala = '1234567890';
if (isPersonKennitala($kennitala)) {
    echo 'Person Kennitala';
} elseif (isCompanyKennitala($kennitala)) {
    echo 'Company Kennitala';
} elseif (isTempKennitala($kennitala)) {
    echo 'Temporary Kennitala';
}
```

### Generating a Kennitala

To generate a technically valid Kennitala, use the `generateKennitala` function:

```php
$generatedKennitala = generateKennitala();
echo $generatedKennitala; // Output: XXXXXXXXXX
```

You can also specify options to generate a specific type of Kennitala:

```php
$options = [
    'type' => 'company',
    'temporary' => true,
    'robot' => false,
    'birthDate' => new DateTime('1990-01-01'),
];
$generatedKennitala = generateKennitala($options);
```

