<?
/**
Should define a $rules array with each conformating to
	Format:
		simpex
			'["'matchAgainst'","'changeInto|changeFunction'","'flag1','flag2'"]'
			changeInto:
				w/o regex flag: replace  entire path with string
				w/ regex flag: replace matched part.  Special syntax for replace uses '['namedGroup']'
					$rules[] = ['/oldDir/(?<path>.*)','/newDir/[path]','301'];
			changeFunction:
				w/o regex flag: receives the matchString, path.  Should return full path replacement
					format:
						1. ':Class::staticMethod', ':Function'
						2. function($matcher,$path){ return 'bob';}
				w/ regex flag: receives matchString, path, matches. Should return match replacement
					format:
						1. ':Class::staticMethod', ':Function'
						2. function($matcher,$path,$matches){ return 'bob';
		Common
			$rules[] = ['/$','/index','regex'];
			$rules[] = ['/oldPath','/newPath','301'];
			$rules[] = ['/oldDir/(?<path>.*)','/newDir/[path]','301'];
			$rules[] = ['/myBase',function(){return 'allYourBaseIsBelongToUs';},'301'];

		each rule should be an array of at least 2 elements, but possibly 3 elements:
			1: the matching string
				the matching string matches against path
			2: the replacement string.  If regex flag is on, replacement string is a preg_replace replacement string excluding the delimiters.  Otherwise, the replacement string will replace the entire path.
			3: the flags

		flags: comma separated string of flags.  There are various flags:
			'once' applies rule once, then ignores it the rest of the time
			'file:last' last rule matched in the file.  Route does not parse any more rules in the containing file, but will parse rules in subsequent files
			'loop:last' is the last matched rule.  Route will just stop parsing rules after this.

			'307' will send http redirect of code 301 (permanent redirect)
			'307' will send http redirect of code 307 (temporary redirect)
			'303' will send http redirect of code 303  (tell client to re-issue as get request)
			'params' will append the query string to the end of the redirect on a http redirect

			'@'finalControlFile in the case the last url token is not loaded, will attempt to load the file specified here
				ex: '@defaultViewLoader' \loads '/control/defaultViewLoader.php'

			'caseless': ignore capitalisation
			'regex': applies regex pattern matching
				last-matched-rule regex-match-groups are saved to Route::$regexMatch
					Note, regex uses '(?<'groupName'>'pattern')' for named matches
					Note, named regexes are extracted (see php "extract") into all control files.

*/

/** @file */
//load index control on paths ending with directory
$rules[] = ['/$','/brushfire/index','regex'];
