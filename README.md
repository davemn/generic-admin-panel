# generic-admin-panel

**Or, "How can I manage [website widget] myself?"**

When building any web-based software, eventually you have to worry about user control.
You've built a beautiful static website, but there are pieces you want your user base to be able to update on their own.

How to make a backend for your users?
Bespoke solutions (a full-fledged CMS, SharePoint, etc.) are too heavyweight.
Big server-side frameworks require too much up-front investment, or don't fit into your stack.

You just need something to put basic edit control in the hands of your users.

> What is this?

This is an exercise of finding the minimum pieces needed to create a web application.
At the heart of any web application is CRUD - Create, Read, Update, Delete.
This project attempts to implement exactly CRUD, and nothing more, on top of a basic persistence layer.

Use this project as a starting point for implementing any admin panel for your website.
It includes item filtering and sorting; editing of detailed attributes; and attaching a file to any item.

> How do I do X?

Several use cases are documented inline in the `Cookbook` section.
For build instructions, refer to the `Prerequisites` section below.

> How was this made?

Functionality is built on a combination of [Backbone](http://backbonejs.org/), [jQuery](http://api.jquery.com/), [Bootstrap](http://getbootstrap.com/), and [LESS](http://lesscss.org/).

Please visit the `Structure` section of this document.
I'll cover the basics there.
If you need to hack on this project, this is the place to start.

## Prerequisites

> What libraries / frameworks / voodoo do I need to know to get started?

A good understanding of jQuery will help gel everything together.
Don't worry if you're not too familiar with Backbone.
In fact, this project (hopefully) serves as a good example of using Backbone.

> How do I get it running out of the box?

You'll need:

* A web server. I've used [Apache](http://apache.org/) running locally on a Windows 7 box. I highly recommend the builds provided by [Apache Lounge](http://apachelounge.com/). You should have good luck with IIS or NGINX.
* PHP, version 5.4+ If you're on Windows, you should use the builds provided at [windows.php.net](http://windows.php.net/download/).
* [Node.js](https://nodejs.org/) - a prerequisite for using Bower and Grunt (below). We'll only be using Node as a command-line tool.
* [Bower](http://bower.io/) - to manage our page's dependencies.
* [Grunt](http://gruntjs.com/) - for specifying and executing our build process. We'll be including other libraries through our Gruntfile, but Grunt will install them for us.

## More to Come!
