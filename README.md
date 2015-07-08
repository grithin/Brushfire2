# Brushfire 2 (version alpha)

A highly scalable, heavily opinionated LAMP framework and tool library.  Not for the feint of heart or easily discouraged.  This github repo serves mostly for framework pulls into projects, but contact me if you want to deploy this framework.

### Opinions
*	The back end should serve as a resource regulater - for data, for server computation, for server peripherals
*	The back end should interact with the front end as an API as much as possible
*	Default assumptions should be made about the model from the database structure (standard column naming)
*	Both the back end and front end should know about and react to the model structure and data
*	The back end should choose what information about the model to provide and should provide an API for accessing and operating on the model data


### Setup
```
git clone https://github.com/grithin/Brushfire2.git brushfire2
cd brushfire2
php install.php /var/www/brushfire.sample
```
This assumes /var/www/brushfire.sample can be a valid directory

Once this is done, point an apache virtual host to /var/www/brushfire.sample/public/


### Instance Layout
*	standard
	*	control/
		*	routes.php
			*	A list of routes.  Without this, urls will map directly to control files (without extension)
		*	control files.  For instance, add test.php and the url path /test  will load it.
	*	tool/
		*	local/
			*	class files with class names of \path\to\file .  One is loaded, if present, that corresponds to the loaded control file
	*	template/
		*	various .php template files.
	*	public/
		*	index.php (copy public/index.php from framework)
		*	static files will be served  statically from this directory.  
			Ex if someone goes ti /test.html, they will get public/test.html
	*	apt/
		*	config.php (see and copy project.config.template.php)
		*	other files that aren't versioned
	*	resource/
		*	non web-framework resources that are	versioned.	Like cli php scripts, or non-php scripts.


### History
Brushfire 2 represents a paradigm shift from Brushfire 1 (v10).  That shift includes moving away from any UI related functionalities in the back end in favor of providing the front end with more data through JSON; use of a central model instead of local tools; forcing the use of the single Control paradigm; forcing use of caching.