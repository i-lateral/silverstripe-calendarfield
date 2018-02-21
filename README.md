# Calendar Field

Adds a calendar field for user-friendly date selection on the front-end.

## Author

This module is created and maintained by [ilateral](http://ilateralweb.co.uk)

## Dependancies

* SilverStripe Framework 3.x

## Installation

Install this module either by downloading and adding to:

[silverstripe-root]/calendarfield

Then run: dev/build/?flush=all

Or alternativly via Composer

`i-lateral/silverstripe-calendarfield`

## Usage

Simply add the calendar field to any form as you would any other field. 
The calendar field will return an array of fields in the submit function for the form.

## Configuration

The calendar field has an array of options that can be called using the 'getOptions()' method.

```php
    protected $options = [
        'day_format' => 'D', // defines the format of the day headings
        'month_format' => 'M', // defines the format of the month
        'year_format' => 'Y', // defines the format of the year
        'allow_past_dates' => true, // can be set to false to disable the selection of dates in the past
        'future_limit' => 100, // number of years into the future the calendar will show
        'past_limit' => 100, // number of years into the past the calendar will show
        'days_count' => 0, // limits the number of days the user will select, default allows the user to select any number
        'StartName' => 'StartDate', // sets the name of the primary date field
        'EndName' => 'EndDate', // sets the name of the secondary date field
        'useEndField' => false // enables the user to select a date range
    ];
```

These options can be overwritten using the 'setOptions($options)' function, providing it with a new array of options  ('$options').