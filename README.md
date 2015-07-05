# Brushfire 2 (version alpha)

A highly scalable, heavily opinionated LAMP framework and tool library.  Not for the feint of heart or easily discouraged.  This github repo serves mostly for framework pulls into projects, but contact me if you want to deploy this framework.

### Opinions
*	The back end should serve as a resource regulater - for data, for server computation, for server peripherals
*	The back end should interact with the front end as an API as much as possible
*	Default assumptions should be made about the model from the database structure (standard column naming)
*	Both the back end and front end should know about and react to the model structure and data
*	The back end should choose what information about the model to provide and should provide an API for accessing and operating on the model data

### History
Brushfire 2 represents a paradigm shift from Brushfire 1 (v10).  That shift includes moving away from any UI related functionalities in the back end in favor of providing the front end with more data through JSON; use of a central model instead of local tools; forcing the use of the single Control paradigm; forcing use of caching.