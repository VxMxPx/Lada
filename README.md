What's Lada?
====
Lada is markup language highly inspired by HAML and Jade. The .lada files are parsed to HTML + PHP.

Warning!!
===
Lada is **not ready** yet, but it will be soon, so stick around.

Quick example
====
<pre>
!DOCTYPE html
html
	head
		meta:charset="utf-8"
		title Hello world
		meta:name="description":content="Sample website title!"
		meta:name="keywords":content="marko, gajst, web development, developer"
		meta:name="author":content="Marko Gajst"
		link:rel="stylesheet":type="text/css":media="screen":href="main.css"
		link:rel="icon":type="image/png":href="favicon.png"
		script
			$('h1').on('click', function() {
				console.log('Hello world!');
			})
		style
			#header {
				float: left;
				background-color: red;
			}
	body
		h1 Hi there, how are you?
		h2
			- if $name
				= Hi there {$name}!
			- else
				I don't know who are you... :O
		
		ul
			li > a:href="http://google.com" Google
			li > a:href="http://yahoo.com" Yahoo

		| Hello world!! :)

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
</pre>