Lada is markup language highly inspired by HAML and Jade. The language is parsed into HTML + PHP.
Please see examples bellow.

<pre>
!DOCTYPE html
html
	head
		meta:charset="utf-8"
		title Hello world
		meta:name="description":content="Web Designer &amp; Web Developer, Maribor Slovenia"
		meta:name="keywords":content="marko, gajst, web development, developer"
		meta:name="author":content="Marko Gajst"
		link:href="http://fonts.googleapis.com/css?family=Ubuntu:300,500,700&amp;subset=cyrillic,latin-ext,latin":rel="stylesheet":type="text/css"
		link:rel="stylesheet":type="text/css":media="screen":href="http://avrelia.com/theme/main.css"
		link:rel="icon":type="image/png":href="http://avrelia.com/theme/favicon.png"
		script
			var _gaq = _gaq || [];
			_gaq.push(['_setAccount', 'UA-5934458-1']);
			_gaq.push(['_trackPageview']);
			(function() {
			var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
			ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
			var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
			})();
	body
		h1 Hi there, how are you?
		h2
			- if $name
				= "Hi there {$name}!"
			- else
				I don't know who are you... :O
		
		ul
			li > a:href="http://google.com" Google
			li > a:href="http://yahoo.com" Yahoo

		Hello world!! :)

		#page.wide
			#header
				#navigation
					a:href="http://google.com" Google
					a:href={url('hello.php')} Hi There

		- foreach $persons as $id => $person
			p ="Hi there {$person}"
			p.logout
				| You can
				a:href={url('logout/id='.$id)} logout here!

		- $name = 'Maya'
		- function upper($str)
			- $str = strtoupper($str)
			- return $str
		= upper($name)

		script
			$('.user').fadeOut('fast');

		style
			#header {
				float: left;
				background-color: red;
			}
</pre>