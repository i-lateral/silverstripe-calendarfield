<?php

class CalendarField extends FormField
{
    protected $startValue;
    protected $endValue;

    private static $allowed_actions = array(
        'calendar'
    );

    private static $url_handlers = array(
        'calendar//$Month/$Year' => 'calendar'
    );
    
    /**
     * Child fields (_StartDate, _EndDate)
     *
     * @var FieldList
     */
    protected $children;
    
    protected $options = [
        'day_format' => 'D',
        'month_format' => 'M',
        'year_format' => 'Y',
        'allow_past_dates' => true,
        'future_limit' => 100,
        'past_limit' => 100,
        'days_count' => 0,
        'StartName' => 'StartDate',
        'EndName' => 'EndDate',
        'useEndField' => false
    ];

    protected $disabled_dates = [];

    /**
     * Create a new file field.
     *
     * @param string $name The internal field name, passed to forms.
     * @param string $title The field label.
     * @param int $value The value of the field.
     */
    public function __construct($name, $title = null, $value = null) {
        $this->children = FieldList::create();
        
        parent::__construct($name, $title, $value);
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function setOptions(array $options) {
        $this->options = array_merge($this->options, $options);

        return $this;
    }

    public function getDisabledDates() 
    {
        return $this->disabled_dates;
    }

    public function setDisabledDates(array $dates)
    {
        $disabled = [];

        foreach ($dates as $date) {
            if ($date instanceof Date) {
                $disabled[] = $date->format("Y-m-d");
            } else if ($date instanceof DateTime) {
                $disabled[] = $date->format('Y-m-d');
            } else {
                $new_date = new DateTime($date);
                $disabled[] = $new_date->format("Y-m-d");
            }
        }

        $this->disabled_dates = $disabled;

        return $this;
    }

    public function getMonth()
    {
        $month = $this->getRequest()->param('Month');
        if ($month) {
            return $month;
        }
        return date('n');
    }

    public function getYear() 
    {
        $year = $this->getRequest()->param('Year');
        if ($year) {
            return $year;
        }
        return date('Y');
    }

    public function setChildren(FieldList $fields) 
    {
        $this->children = $fields;

        return $this;
    }

    /**
     * Returns the children of this field for use in templating.
     * @return FieldList
     */
    public function getChildren() 
    {
        return $this->children;
    }

    /**
     * Set the field value.
     *
     * @param mixed $value
     * @param null|array|DataObject $data {@see Form::loadDataFrom}
     *
     * @return $this
     */
    public function setValue($value) 
    {
        if(is_array($value)) {
            $this->value = [
                $this->options['StartName'] => $value[$this->options['StartName']],
                $this->options['EndName'] => $value[$this->options['EndName']]
            ];

            $this->startValue = $value[$this->options['StartName']];
            $this->endValue = $value[$this->options['EndName']];
        }

        return $this;
    }

    
    public function getCalendarDays($month,$year)
    {
        /* days in month */
        $days = ArrayList::create();

        /* days and weeks vars now ... */
        $running_day = date('w', mktime(0, 0, 0, $month, 1, $year));
        if ($running_day < 1) {
            $running_day = 7;
        }
        $days_in_month = date('t', mktime(0, 0, 0, $month, 1, $year));
        $days_last_month = date('t', mktime(0, 0, 0, ($month - 1), 1, $year));
        $days_in_this_week = 1;
        $day_counter = 0;
        $dates_array = array();

        /* print "blank" days until the first of the current week */
        for ($x = 1; $x < $running_day; $x++) {
            $datetime = new DateTime($year . '-' . $month . '-01');
            $datetime->modify('- ' . ($running_day - $x) . ' days');
            $date = new Date();
            $date->setValue($datetime->format('Y-m-d'));
            $day = ArrayData::create(
                [
                    'InMonth' => false,
                    'Number' => $datetime->format('d'),
                    'Date' => $date,
                    'Selectable' => true
                ]
            );
            $days->push($day);
            $days_in_this_week++;
        }
        if ($running_day == 7) {
            $running_day = 0;
        }

        /* keep going with days.... */
        for ($list_day = 1; $list_day <= $days_in_month; $list_day++) {
            $date = new Date();
            $date->setValue($year . '-' . $month . '-' . $list_day);
            $day = ArrayData::create(
                [
                    'InMonth' => true,
                    'Number' => $list_day,
                    'Date' => $date,
                    'Selectable' => true
                ]
            );

            $days->push($day);

            if ($running_day == 6) {
                $running_day = -1;
                $days_in_this_week = 0;
            }
            $days_in_this_week++; $running_day++; $day_counter++;
        }

        /* finish the rest of the days in the week */
        if ($days_in_this_week < 8) {
            for ($x = 1; $x <= (9 - $days_in_this_week); $x++) {
                $date = new Date();
                $date->setValue(
                    date('Y-m-d', mktime(0, 0, 0, ($month + 1), $x, $year))
                );
                $day = ArrayData::create([
                    'InMonth' => false,
                    'Number' => $x,
                    'Date' => $date,
                    'Selectable' => true
                ]);
                $days->push($day);
            }
        }

        foreach ($days as $day) {
            if (!in_array($day->Date->format("Y-m-d"), $this->disabled_dates)
            ) {
                $day->Availability = 'available';
                $day->Lock = false; 
                $day->Selectable = true;
            } else {
                $day->Availability = 'not-available';
                $day->Lock = true; 
                $day->Selectable = false;
            }
        }

        return $days;
    }

    public function getCalendarHeadings()
    {
        $headings = ArrayList::create();
        
        foreach ($this->getDaysOfWeek() as $heading) {
            $headings->push(
                ArrayData::create(
                    [
                        'Day' => $heading
                    ]
                )
            );
        }

        return $headings;
    }

    /* draws a calendar */
    function calendar() {

        $today = new Date();
        $today->setValue(date("Y-m-d H:i:s"));
        
        $month = $this->getMonth();
        $year = $this->getYear();

        /* draw table */
        $calendar = ArrayList::create();

        $days = $this->getCalendarDays($month, $year);

        $back = $this->getBackLink();
        $next = $this->getNextLink();
        $month = $this->getMonthField();
        $year = $this->getYearField();

        $headings = $this->getCalendarHeadings();

        $this->extend('updateCalendar', $days);

        return $this->renderWith(
            self::class,
            [
                'DayHeadings' => $headings,
                'BackLink' => $back,
                'NextLink' => $next,
                'MonthField' => $month,
                'YearField' => $year,
                'Days' => $days
            ]
        );
    }

    public function Field($properties = array())
    {
        $this->children->add(
            HiddenField::create(
                $this->getName().'['.$this->options['StartName'].']'
            )->setAttribute('data-calendar', 'StartDate')
        );

        if ($this->options['useEndField']) {
            $this->children->add(
                HiddenField::create(
                    $this->getName().'['.$this->options['EndName'].']'
                )->setAttribute('data-calendar', 'EndDate')
            );
        }

        return $this->calendar();
    }

    public function getDaysOfWeek()
    {
        $days = [];
        $day_start = date( "d", strtotime( "next Monday" ) );
        for ($d=0; $d<7; $d++) {
            $days[] = date($this->options['day_format'], mktime(0,0,0,date( "m" ), $day_start + $d, date('Y')));
        }
        
        return $days;
    }

    public function getBackLink()
    {
        $month = $this->getMonth();
        $year = $this->getYear();

        $date = new DateTime($year . '-' . $month . '-01');
        $date->modify('-1 month');

        return Controller::join_links(
            $this->Link('calendar'),
            $date->format('n'),
            $date->format('Y')
        );
    }

    public function getNextLink()
    {
        $month = $this->getMonth();
        $year = $this->getYear();

        $date = new DateTime($year . '-' . $month . '-01');
        $date->modify('+1 month');

        return Controller::join_links(
            $this->Link('calendar'),
            $date->format('n'),
            $date->format('Y')
        );       
    }

    public function getMonthField()
    {
        $current_month = $this->getMonth();
        $months = [];

        for ($m=1; $m<=12; $m++) {
            $month = date($this->options['month_format'], mktime(0,0,0,$m, 1, date('Y')));
            $months[$m] = $month;
        }

        return DropdownField::create(
            'Calendar[Month]',
            'Month',
            $months
        )->setValue($current_month);
    }

    public function getYearField()
    {
        $this_year = date('Y');
        $current_year = $this->getYear();
        $latest_year = $this_year + $this->options['future_limit'];

        if ($this->options['allow_past_dates']) {
            $earliest_year = $this_year - $this->options['past_limit'];
        } else {
            $earliest_year = $this_year;
        }

        $years = [];

        foreach ( range( $latest_year, $earliest_year ) as $i ) {
            $years[$i] = date($this->options['year_format'], mktime(0,0,0,1, 1, $i));
        }

        return DropdownField::create(
            'Calendar[Year]',
            'Year',
            $years
        )->setValue($current_year);
    }

        /**
     * Allows customization through an 'updateAttributes' hook on the base class.
     * Existing attributes are passed in as the first argument and can be manipulated,
     * but any attributes added through a subclass implementation won't be included.
     *
     * @return array
     */
    public function getAttributes() {
        $attributes = array(
            'name' => $this->getName(),
            'class' => $this->extraClass(),
            'id' => $this->ID(),
            'disabled' => $this->isDisabled(),
            'readonly' => $this->isReadonly(),
            'data-url' => $this->Link('calendar'),
            'data-days' => $this->options['days_count']
        );

        if($this->Required()) {
            $attributes['required'] = 'required';
            $attributes['aria-required'] = 'true';
        }

        $attributes = array_merge($attributes, $this->attributes);

        $this->extend('updateAttributes', $attributes);

        return $attributes;
    }

    /**
     * Validate this field
     *
     * @param Validator $validator
     * @return bool
     */
    public function validate($validator) {
        if (!$this->options['useEndField']) {
            if (!$this->startValue) {
                $validator->validationError(
                    $this->name,
                    _t(
                        'CalendarField.NO_DATE',
                        "Please select a date.",
                        array('value' => $this->value)
                    ),
                    "validation"
                );

                return false;
            }
        } else {
            if (!$this->startValue) {
                $validator->validationError(
                    $this->name,
                    _t(
                        'CalendarField.NO_DATE',
                        "Please select a start date.",
                        array('value' => $this->value)
                    ),
                    "validation"
                );
                return false;
            }

            if (!$this->endValue) {
                $validator->validationError(
                    $this->name,
                    _t(
                        'CalendarField.NO_END_DATE',
                        "Please select an end date.",
                        array('value' => $this->value)
                    ),
                    "validation"
                );
                return false;
            }

            if ($this->startValue > $this->endValue) {
                $validator->validationError(
                    $this->name,
                    _t(
                        'CalendarField.INVALID_DATES',
                        "The end date needs to be after the start date.",
                        array('value' => $this->value)
                    ),
                    "validation"
                );
                return false;
            }
        }
        return true;
    }
}