# HTML Calendar to ICS

Use this app to pull any HTML based calendar from the web and create an ICS file for your calendar. Custom calendars can be added in config/calendars using the .ini format. 

To get started, clone the repo, and run 

```
cd public
php -S 0.0.0.0:8080 
```

## INI Configuration options
All configuration files must have a [calendar] key.

Example:

```
[calendar]
name="World Health Edgemont" 
url="http://www.worldhealth.ca/whc/calendar/search.aspx"
url_post_data="c=40"
xpath_days="//table/tbody/tr/td/p[contains(@class, "date")]"
xpath_events="//table/tbody/tr/td/p[contains(@class, "class") and ../p[contains(@class, "date")] and ../p[text() = '%%DAY%%']]"
xpath_event_description="//"
xpath_event_start_time="//span"
xpath_event_end_time="//span"
xpath_event_start_day="//../p[contains(@class, 'date')]"
xpath_event_end_day="//../p[contains(@class, 'date')]"
xpath_event_location="string('')"
timezone="America/Edmonton"
```

As shown, xpath is used to build the ICS from the page. 

## TODO
- fix empty node problem
- add data transformers to modify resulting node from query
