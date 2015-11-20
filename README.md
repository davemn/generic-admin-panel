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

## Structure

Like any [Backbone](http://backbonejs.org/) application, this one starts with a model and a set of nested views.

### Structure - Frontend Model

A data model is a fundamental part of every application.
Complex applications define [multiple entities / classes](https://en.wikipedia.org/wiki/Conceptual_schema), with relationships between each.

For the sake of simplicity, the Generic Admin Panel web application describes exactly one entity - `ChangeRequestModel`.
`ChangeRequestModel` is meant to be an **example** entity in your application's problem domain.
You should alter its name and attributes to fit your needs.

As it is defined in the application out-of-the-box, the `ChangeRequestModel` entity has the following attributes:

* `external_id` - An identifier to match this record to one in an external system. Use if you're tracking entities that originate in a parent [system of record](https://en.wikipedia.org/wiki/System_of_record).
* `file_id` - The MD5 sum of the file associated with this record.
* `area`
* `project`
* `type` - E.g. "Router Configuration". **Not** used to track any data type.
* `date` - M/D/YY
* `owner`
* `agency`
* `priority`
* `status`
* `result`
* `category`

### Structure - Frontend Model Persistence

To interface with the persistence layer (see below), I'm overriding Backbone's [sync method](http://backbonejs.org/#Sync) on the `ChangeRequestModel` entity.
From Backbone's documentation:

> The default sync handler maps CRUD to REST like so:
> Action | Verb
> --- | ---
> create | POST `/collection`
> read | GET `/collection[/id]`
> update | PUT `/collection/id`
> ~~patch | PATCH `/collection/id`~~
> delete | DELETE `/collection/id`

As you can see, I'm not supporting the `PATCH` HTTP verb for the time being.

Each action is mapped to a method on `ChangeRequestModel`:

Action | Method
------ | ------
create | `ChangeRequestModel::serverCreate()`. Uses the [`FormData`](https://developer.mozilla.org/en-US/docs/Web/API/FormData) browser API to POST  `multipart/form-data` requests, including a `_payload_` section (holds JSON of the model's attributes) and an optional `attachment` section (holds a single binary file attachment).
read   | Uses the default `Model::sync()` implementation.
update | `ChangeRequestMode::serverUpdate() | serverUpdateWithAttachment()`. PUTs `application/json` requests, whose body is a JSON representation of the model, with an optional `"attachment"` key mapping to a data URL encoding of the file attachment (see [`FileReader::readAsDataURL()`](https://developer.mozilla.org/en-US/docs/Web/API/FileReader/readAsDataURL).
delete | Uses the default `Model::sync()` implementation.
    
Ultimately, communication with the persistence layer can be as simple or complex as you'd like.
In particular, the verbs you support and the format of your HTTP requests are both choices you'll have to make.

* The verbs you support depend on how much of CRUD you'd like to implement.
* The format of your HTTP requests depends on your opinion on how REST should be implemented. Using plain JSON request bodies, for example, you can use Backbone's sync method without modification. 

The solution I've reached above attempts to add support for file uploads, while still being true to the Backbone API and RESTful principles.
That's why I've opted to use `multipart/form-data` requests for the `POST` verb, but `application/json` requests for `PUT`.

### Structure - Backend Model Persistence

Out of the box, your model is persisted to server-side storage using a simple RESTful web service.
I do mean simple - a PHP script performs CRUD operations on a single JSON file.
The web service is contained entirely in the `rest/` directory of this repo.
[Take a look](https://github.com/davemn/generic-admin-panel/tree/master/rest), it has its own README.

## More to Come!
