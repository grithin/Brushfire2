<h2>Getting started</h2>
<p>This template is showing as a result of both the control/routes.php file and the template/brushfire/aliases.php file</p>
<p>The control file for this page has</p>
<blockquote>
	View::end('@common');
</blockquote>
<p>@common is a template alias that tells the View class to load the current page along with some other templates</p>
<p>For this page, that looks like:</p>
<pre>
	load template/brushfire/index
	load template/brushfire/header
	load template/brushfire/top
</pre>
<p>You can set up custom routes in control/routes.php and custom template aliase in template/aliases.php</p>