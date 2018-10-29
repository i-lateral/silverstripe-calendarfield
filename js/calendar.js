jQuery.noConflict();

(function($) {
    function calendarAjax($url) 
    {
        $field = $('.calendarfield');
        $table = $('.calendarfield table');
        $start_field = $('input[data-calendar=StartDate]');
        $end_field = $('input[data-calendar=EndDate]');
        $start_date = $start_field.val();
        $end_date = $end_field.val();
        $link = $field.attr('data-url');
        $month_field = $table.find('#Calendar_Month');
        $year_field = $table.find('#Calendar_Year');
        $month = $month_field.val();
        $year = $year_field.val();
        if ($url == null) {
            $url = $link+'/'+$month+'/'+$year;
        }
        $table.append('<div class="preloader-holder"><div class="preloader"><span class="preloader-icon">&#10227;</span></div></div>');
        $.get($url,function($data) {
            $table.replaceWith($($data).find('table'));
            $start_field = $('input[data-calendar=StartDate]');
            $end_field = $('input[data-calendar=EndDate]');
            $start_field.val($start_date);
            $end_field.val($end_date);
            if ($start_field.val() && $end_field.val()) {
                selectDates($start_field.val(),$end_field.val());
            }
        });
    }

    function selectDates($start,$end) 
    {
        $valid = checkDates($start,$end);

        $start_date = new Date($start);
        $end_date = new Date($end);
        $end_date.setDate($end_date.getDate()+1);
        $table = $('.calendarfield');
        $dates = $table.find('td');
        $days = $table.attr('data-days');

        if (!$valid) {
            if ($days == 0) {
                deselectAllDates(false);
            } else {
                deselectAllDates();
            }
        } else {
            $dates.each(function() {
                $curr_time = new Date($(this).attr('data-date')).getTime();
                if ($curr_time >= $start_date.getTime() && $curr_time <= ($end_date.getTime()-1)) {
                    $(this).addClass('selected');
                } else {
                    $(this).removeClass('selected');
                }
            });
        }
    }

    function checkDates($start,$end) 
    {
        $start_date = new Date($start);
        $end_date = new Date($end);
        $end_date.setDate($end_date.getDate()+1);
        $table = $('.calendarfield');
        $dates = $table.find('td');
        $valid = true;
        $dates.each(function() {
            $curr_time = new Date($(this).attr('data-date')).getTime();
            if ($curr_time >= $start_date.getTime() && $curr_time <= ($end_date.getTime()-1) && $(this).hasClass('not-available')) {
                $valid = false;
            }
        });

        return $valid;
    }

    function hoverDates($start,$end) 
    {
        $start_date = new Date($start);
        $end_date = new Date($end);
        $end_date.setDate($end_date.getDate()+1)
        $table = $('.calendarfield');
        $dates = $table.find('td');
        $dates.each(function() {
            $curr_time = new Date($(this).attr('data-date')).getTime();
            if ($curr_time >= $start_date.getTime() && $curr_time <= ($end_date.getTime()-1)) {
                $(this).addClass('hover');
            } else {
                $(this).removeClass('remove');
            }
        });
    }

    function deselectAllDates($start) {
        if ($start === undefinmed) {
            $start = true;
        }
        $table = $('.calendarfield');
        $start_field = $('input[data-calendar=StartDate]');
        $end_field = $('input[data-calendar=EndDate]');
        if ($start) {
            $start_field.val('');
        }
        $end_field.val('');
        $dates = $table.find('td');
        $dates.each(function() {
            $(this).removeClass('selected');
            $(this).removeClass('hover');
            if (!$start && $(this).attr('data-date') == $start_field.val()) {
                $(this).addClass('selected');
            }
        });
    }

    function removeHover() {
        $table = $('.calendarfield');
        $dates = $table.find('td');
        $dates.each(function() {
            $(this).removeClass('hover');
        });
    }

	$(document).ready(function() {
        $(document).on('change','.calendarfield select',function() {
            calendarAjax();
        });

        $(document).on('click','.calendarfield .direction-link',function(e) {
            e.preventDefault();
            calendarAjax($(this).attr('href'));
        });

        $(document).on('mouseenter','.calendarfield .available',function() {
            $table = $('.calendarfield');
            $days = $table.attr('data-days');
            if ($days > 0) {
                removeHover();
                $curr_date = new Date($(this).attr('data-date'));
                $next_date = new Date($(this).attr('data-date'));
                $next_date.setDate($curr_date.getDate() + (parseInt($days) - 1));
                $date_string = $next_date.getFullYear() + '-' + (parseInt($next_date.getMonth()) + 1) + '-' + $next_date.getDate();
                hoverDates($(this).attr('data-date'),$date_string); 
            }
        });

        $(document).on('mouseleave','.calendarfield .calendar-row',function() {
            $table = $('.calendarfield');
            $days = $table.attr('data-days');
            if ($days > 0) {
                removeHover();
            }

        });

        $(document).on('mouseenter','.calendarfield .selected',function() {
            $table = $('.calendarfield');
            $dates = $table.find('td.selected');
            $dates.each(function() {
                $(this).addClass('hover');
            });
        });

        $(document).on('mouseleave','.calendarfield .selected',function() {
            $table = $('.calendarfield');
            $dates = $table.find('td.selected');
            $dates.each(function() {
                $(this).removeClass('hover');
            });
        });

        $(document).on('touchstart click','.calendarfield .available, .calendarfield .selected',function() {
            console.log('clicky');
            $table = $('.calendarfield');
            $days = $table.attr('data-days');
            $start_field = $('input[data-calendar=StartDate]');
            $end_field = $('input[data-calendar=EndDate]');
            if ($(this).hasClass('selected')) {
                deselectAllDates();
            } else {
                if ($start_field.length > 0 && $days > 0 ) {
                    $start_field.val($(this).attr('data-date'));
                    if ($end_field.length > 0) {
                        $next_date = new Date($(this).attr('data-date'));
                        $next_date.setDate($next_date.getDate()+(parseInt($days)-1));
                        $date_string = $next_date.getFullYear()+'-'+("0" + ($next_date.getMonth() + 1)).slice(-2)+'-'+$next_date.getDate();
                        $end_field.val($date_string);
                        selectDates($start_field.val(),$end_field.val()); 
                    } else {
                        selectDates($start_field.val(),$start_field.val()); 
                    }
                } else if ($start_field.length > 0 && !$start_field.val()) {
                    $start_field.val($(this).attr('data-date'));
                    $(this).addClass('selected');
                } else if ($end_field.length > 0 && !$end_field.val()) {
                    if (new Date($(this).attr('data-date')).getTime() > new Date($start_field.val()).getTime()) {
                        $end_field.val($(this).attr('data-date'));
                    } else {
                        $end_field.val($start_field.val());
                        $start_field.val($(this).attr('data-date'));
                    }
                    selectDates($start_field.val(),$end_field.val());            
                } else {
                    if (new Date($(this).attr('data-date')).getTime() < new Date($start_field.val()).getTime()) {
                        $start_field.val($(this).attr('data-date'));
                    } else if (new Date($(this).attr('data-date')).getTime() > new Date($end_field.val()).getTime()) {
                        $end_field.val($(this).attr('data-date'));
                    } else {
                        $start_field.val('');
                        $end_field.val('');
                    }
                    if ($start_field.val() && $end_field.val()) {
                        selectDates($start_field.val(),$end_field.val()); 
                    } else {
                        deselectAllDates();
                    }           
                }
            }
        });
    });
}(jQuery));