<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	https://codeigniter.com/userguide3/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There are three reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router which controller/method to use if those
| provided in the URL cannot be matched to a valid route.
|
|	$route['translate_uri_dashes'] = FALSE;
|
| This is not exactly a route, but allows you to automatically route
| controller and method names that contain dashes. '-' isn't a valid
| class or method name character, so it requires translation.
| When you set this option to TRUE, it will replace ALL dashes in the
| controller and method URI segments.
|
| Examples:	my-controller/index	-> my_controller/index
|		my-controller/my-method	-> my_controller/my_method
*/
$route['default_controller'] = 'welcome';
$route['404_override'] = '';
$route['translate_uri_dashes'] = TRUE;

// API Auth Routes
$route['api/auth/login']   = 'api/auth/login';
$route['api/auth/me']      = 'api/auth/me';
$route['api/auth/refresh'] = 'api/auth/refresh';
$route['api/auth/logout']  = 'api/auth/logout';

// API Users
$route['api/users/technicians'] = 'api/users/technicians';
$route['api/users']             = 'api/users/index';

// API Rooms
$route['api/rooms']               = 'api/rooms/index';
$route['api/rooms/store']         = 'api/rooms/store';
$route['api/rooms/(:num)']        = 'api/rooms/show/$1';
$route['api/rooms/update/(:num)'] = 'api/rooms/update/$1';

// API Categories
$route['api/categories'] = 'api/categories/index';

// API Equipment
$route['api/equipment']               = 'api/equipment/index';
$route['api/equipment/lookup']        = 'api/equipment/lookup';
$route['api/equipment/store']         = 'api/equipment/store';
$route['api/equipment/(:num)']        = 'api/equipment/show/$1';
$route['api/equipment/update/(:num)'] = 'api/equipment/update/$1';
$route['api/equipment/delete/(:num)'] = 'api/equipment/delete/$1';

// API Work Orders
$route['api/work-orders']                  = 'api/workorders/index';
$route['api/work-orders/store']            = 'api/workorders/store';
$route['api/work-orders/update/(:num)']    = 'api/workorders/update/$1';
$route['api/work-orders/delete/(:num)']    = 'api/workorders/delete/$1';
$route['api/work-orders/(:num)']           = 'api/workorders/show/$1';
$route['api/work-orders/assign/(:num)']    = 'api/workorders/assign/$1';
$route['api/work-orders/progress/(:num)']  = 'api/workorders/progress/$1';
$route['api/work-orders/add-part/(:num)']  = 'api/workorders/add_part/$1';
$route['api/work-orders/verify/(:num)']    = 'api/workorders/verify/$1';

// API Spareparts
$route['api/spareparts']               = 'api/spareparts/index';
$route['api/spareparts/store']         = 'api/spareparts/store';
$route['api/spareparts/update/(:num)'] = 'api/spareparts/update/$1';

// API Preventive
$route['api/preventive/schedules']       = 'api/preventive/schedules';
$route['api/preventive/store']           = 'api/preventive/store';
$route['api/preventive/update/(:num)']   = 'api/preventive/update/$1';
$route['api/preventive/delete/(:num)']   = 'api/preventive/delete/$1';
$route['api/preventive/complete/(:num)'] = 'api/preventive/complete/$1';
$route['api/preventive/(:num)']          = 'api/preventive/show/$1';

// API Calibrations
$route['api/calibrations']                 = 'api/calibrations/index';
$route['api/calibrations/expiring']        = 'api/calibrations/expiring';
$route['api/calibrations/store']           = 'api/calibrations/store';
$route['api/calibrations/update/(:num)']   = 'api/calibrations/update/$1';
$route['api/calibrations/delete/(:num)']   = 'api/calibrations/delete/$1';
$route['api/calibrations/(:num)']          = 'api/calibrations/show/$1';

// API Calendar
$route['api/calendar/events'] = 'api/calendar/events';

// API Dashboard
$route['api/dashboard/summary'] = 'api/dashboard/summary';

// API Users Update
$route['api/users/update/(:num)'] = 'api/users/update/$1';

// API Audit Logs
$route['api/audit-logs'] = 'api/auditlogs/index';

// API Reports
$route['api/reports/recap'] = 'api/reports/recap';

// API Settings (Hospital Configuration)
$route['api/settings']        = 'api/settings/index';
$route['api/settings/update'] = 'api/settings/update';

