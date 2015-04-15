<?php
  function print_access_denied(){
    $err_page = <<<HTML
<!DOCTYPE html>
<html>
  <head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta charset="utf-8">
  </head>
  <body>
    <h2>A Problem Occurred</h2>
    <p>
      We were unable to log you in. Please check your user name and password, or try again. Please notify the webmaster if you believe this is in error.
    </p>
  </body>
</html>
HTML;
    echo $err_page;
  }
  
  function challenge_user(){
    header('WWW-Authenticate: Basic realm="Example Admin"');
    header('HTTP/1.0 401 Unauthorized');
    $err_page = <<<HTML
<!DOCTYPE html>
<html>
  <head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta charset="utf-8">
  </head>
  <body>
    <h2>A Problem Occurred</h2>
    <p>
      We were unable to log you in. Please check your user name and password, or try again. Please notify the webmaster if you believe this is an error.
    </p>
  </body>
</html>
HTML;
    echo $err_page;
  }
  
  // Never logged in before, or cache cleared
  if (!isset($_SERVER['PHP_AUTH_USER'])) {
    challenge_user();
    exit;
  }
  
  $valid_passwords = array(
    "WebTeam@example.com" => "divs&spans"
  );
  $valid_users = array_keys($valid_passwords);

  $user = $_SERVER['PHP_AUTH_USER'];
  $pass = $_SERVER['PHP_AUTH_PW'];

  $validated = (in_array($user, $valid_users)) && ($pass == $valid_passwords[$user]);

  // Bad username / password
  if (!$validated) {
    challenge_user();
    exit;
  }
  
  session_start();
  if(!array_key_exists('timeout', $_SESSION)){
    $_SESSION['timeout'] = time();
  }
  
  // Currently logged in, but for too long
  $timeout = 2; // in minutes
  if($_SESSION['timeout'] + ($timeout*60) < time()){ // we've reached the timeout limit
    unset($_SESSION['timeout']);
    challenge_user();
    exit;
  }
?>
<!DOCTYPE html>
<html>
  <head>
    <title>Generic Backbone Admin Panel</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta charset="utf-8">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="shortcut icon" href="img/favicon.png">
    <!-- Bootstrap core CSS + customizations -->
    <link rel="stylesheet" type="text/css" href="index.css">
    <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
    <script src="bower_components/html5shiv/dist/html5shiv.js"></script>
    <script src="bower_components/respond/dest/respond.min.js"></script>
    <![endif]-->
  </head>
  <body>
    <section class='section-condensed' id='section-iconbar'>
      <div class='container-fluid'>
        <div class='row'>
          <div class='col-xs-2'>
            <h1>
              <i class="fa fa-cog"></i>
              Admin
            </h1>
          </div>
          <div class='col-xs-10'>
            <h1>Example Entries</h1>
            <h1 class='pull-right' id='col-details-head'>
              <a href="#action-new">
                <i class='fa fa-fw fa-plus-circle'></i>
              </a>
              <a href="#action-save">
                <i class='fa fa-fw fa-floppy-o'></i>
              </a>
              <a href="#action-delete">
                <i class='fa fa-fw fa-trash'></i>
              </a>
            </h1>
          </div>
        </div>
      </div>
    </section>
    <section class='section-condensed'>
      <div class='container-fluid'>
        <div class='row'>
          <div class='col-xs-2' id='col-filters'>
            <div class="list-group">
              <a class="list-group-item" href="#filter-all"><i class="fa fa-home fa-fw"></i>All</a>
              <a class="list-group-item" href="#filter-current"><i class="fa fa-sun-o fa-fw"></i>Current</a>
              <a class="list-group-item" href="#filter-archived"><i class="fa fa-database fa-fw"></i>Archived</a>
              <a class="list-group-item" href="#filter-future"><i class="fa fa-rocket fa-fw"></i>Future</a>
            </div>
          </div>
          <div class='col-xs-6'>
            <table class="table table-hover" id='change-table'>
              <thead>
                <tr>
                  <th data-model-attribute='project'>Project</th>
                  <th data-model-attribute='owner'>Owner</th>
                  <th data-model-attribute='date'>Date Requested<i class='fa fa-caret-down'></i></th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
          <div class='col-xs-4 col-vr col-vr-default' id='col-details'></div>
        </div>
    </section>
    <!-- Placed at the end of the document so the pages load faster -->
    <script type="text/javascript" src="bower_components/jquery/dist/jquery.min.js"></script>
    <script type="text/javascript" src="bower_components/underscore/underscore-min.js"></script>

    <script type="text/javascript" src="bower_components/handlebars/handlebars.runtime.min.js"></script>
    <script type="text/javascript" src="views/table_item.handlebars.js"></script>
    
    <script type="text/javascript" src="bower_components/backbone/backbone.js"></script>
    <script type="text/javascript" src="bower_components/bootstrap/dist/js/bootstrap.min.js"></script>
    <script>
      $(window).load(function() {
        
      });
           
      $(document).ready(function(evt) {
        var ChangeRequestModel = Backbone.Model.extend({
          urlRoot: 'api/change_request',
          defaults: {
            external_id: '',
            file_id:  '',
            area:     '',
            project:  '',
            type:     '',
            date: "<?php echo (new DateTime())->format('n/j/y'); ?>",
            owner:    '',
            agency:   '',
            priority: 'Normal',
            status:   'Queued',
            result:   '-',
            category: 'Current'
          },
          // If you define an initialize function, it will be invoked when the model is created. 
          initialize: function() { },
          setToday: function() {
            // e.g. "8/2/16"
            this.set({date: "<?php echo (new DateTime())->format('n/j/y'); ?>"});
          },
          // Like Model.attributes, except the result only includes attributes retrievable by Model.get()
          getAll: function(){
            var clean = {};
            for(var attr in this.defaults){
              if(this.defaults.hasOwnProperty(attr)){
                clean[attr] = this.get(attr);
              }
            }
            if('id' in this) // since 'id' is not part of the default attribute set
              clean.id = this.id;
            return clean;
          },
          serverCreate: function(model, opts){ // POST
            // You need to fire either options.success or options.error depending on whether the method succeeded.
            // opts.attachment := Should be a JS File object, retrieved from the DOM
            //                    using, e.g., find('input[name="attachment"]').get(0).files[0]
            var blobBody = JSON.stringify(model.getAll());
            var blobPart = new Blob([blobBody], { type: "application/json" });
            
            var form = new FormData();
            
            form.append("_payload_", blobPart);
            if(typeof opts.attachment !== "undefined")
              form.append("attachment", opts.attachment);
            
            $.ajax({
              type: "POST",
              url: this.urlRoot,
              processData: false,
              contentType: false, // note, this is a multipart form request. 
              data: form
            })
            .done(function(data, status, resp){
              // console.log(data);
              // (model, response, options)
              
              // Create all the attributes the server is responsible for creating on a new model
              model.id = data.id;
              if('file_id' in data)
                model.file_id = data.file_id;
            
              if(typeof opts.success !== "undefined")
                opts.success(model, resp, opts);
            })
            .fail(function(resp){
              // console.log("POST operation failed!");
              // 
              // var errObj = $.parseJSON(resp.responseText);
              // console.log(errObj.error);
              if(typeof opts.error !== "undefined")
                opts.error(model, resp, opts);
            });
          },
          onFileAttached: function(evt){
            // evt.target.result
            // evt.target.error
            // evt.target.error.name
            
            // "evt" is an event object generated by a FileReader object.
            // "evt.target" is the FileReader object that generated the event.
            // evt.target.error  := "A DOMError representing the error that occurred while reading the file."
            // evt.target.result := "The file's contents."
            
            // Unpack our args from the event object
            var reader = evt.target;
            var model  = evt.data.model;
            var opts   = evt.data.opts;
            
            if(reader.error !== null){
              if(typeof opts.error !== "undefined")
                opts.error(
                  model, 
                  { responseText: '{"error": "An error occurred when trying to read the file for upload!"}' }, 
                  opts);
              return;
            }
            
            // evt.data.model.[id|file_id|...]
            // evt.data.attachment.[name|type]
            // evt.data.opts.[success|error]
            
            var content = model.getAll();
            content['attachment'] = {
              name: opts.attachment.name,
              type: opts.attachment.type, // MIME type
              dataurl: reader.result
            };
            
            $.ajax({
              type: "PUT",
              url: model.urlRoot + '/' + model.id,
              processData: false,
              contentType: 'application/json',
              data: JSON.stringify(content)
            })
            .done(function(data, status, resp){
              // Update all the attributes the server is responsible for on an existing model
              model.id = data.id;
              if('file_id' in data)
                model.file_id = data.file_id;
              
              if(typeof opts.success !== "undefined")
                opts.success(model, resp, opts);
            })
            .fail(function(resp){
              if(typeof opts.error !== "undefined")
                opts.error(model, resp, opts);
            });
          },
          serverUpdateWithAttachment: function(model, opts){ // PUT
            // You need to fire either options.success or options.error depending on whether the method succeeded.
            // opts.attachment := Should be a JS File object, retrieved from the DOM
            //                    using, e.g., find('input[name="attachment"]').get(0).files[0]
            
            var args = {};
            args.model = model;
            args.opts = {};
            if(typeof opts.success !== "undefined")
              args.opts.success = opts.success;
            if(typeof opts.error !== "undefined")
              args.opts.error = opts.error;
            args.opts.attachment = opts.attachment;
            
            var attachReader = new FileReader();
            $(attachReader).on('loadend', args, this.onFileAttached);
            attachReader.readAsDataURL(opts.attachment);
          },
          serverUpdate: function(model, opts){ // PUT
            var modelAttrs = model.getAll();
          
            $.ajax({
              type: "PUT",
              url: this.urlRoot + '/' + modelAttrs.id,
              processData: false,
              contentType: 'application/json',
              data: JSON.stringify(modelAttrs)
            })
            .done(function(data, status, resp){
              if(typeof opts.success !== "undefined")
                opts.success(model, resp, opts);
            })
            .fail(function(resp){
              if(typeof opts.error !== "undefined")
                opts.error(model, resp, opts);
            });
          },
          sync: function(method, model, opts){
            switch(method){
            default:
            case 'read':
            case 'delete':
              return Backbone.sync(method, model, opts);
            case 'create':
              return this.serverCreate(model, opts);
            case 'update':
              if(typeof opts.attachment === "undefined")
                return this.serverUpdate(model, opts);
              else
                return this.serverUpdateWithAttachment(model, opts);
              break;
            }
          }
        });
        
        Backbone.Collection.prototype.close = function(){
          this.off();
          this.stopListening();
          if(this.onClose){
            this.onClose();
          }
        }
        
        var ChangeRequestCollection = Backbone.Collection.extend({
          model: ChangeRequestModel,
          selectedId: null,
          initialize: function(){            
            this.on('select', this.setSelected);   // fired by model instances that can't see their parent collection
            this.on('change', this.sort);          // re-sort anytime a model in this collection changes, in case the attr changed was the one currently sorted by
            this.on('add', this.addAndSelect);     // adding a model also selects it
            this.on('remove', this.removeAndSelect);
          },
          setSelected: function(selectedId){
            this.selectedId = selectedId;
          },
          getSelected: function(){
            // If we've never set the selected model, use the first one in the collection (if any)
            if(this.selectedId === null){
              if(this.length === 0)
                return null; // can't return the first element if there is none
              this.setSelected(this.at(0).get('id'));
            }
          
            var found = _.find(this.models, function(model){
                return model.get('id') === this.selectedId;
              }, this);
            if(typeof found === "undefined")
              return null;  // coerce Underscore's 'undefined' return value into a simpler-to-check null
            return found;
          },
          getSelectedIdx: function(){
            // If we've never set the selected model, use the first one in the collection (if any)
            if(this.selectedId === null){
              if(this.length === 0)
                return -1; // can't return the first element if there is none
              this.setSelected(this.at(0).get('id'));
            }
          
            var foundIdx = -1;
            
            _.each(this.models, function(model,idx){
              if(model.get('id') === this.selectedId)
                foundIdx = idx;
              }, this);
              
            return foundIdx;       
          },
          addAndSelect: function(model, collection, opts){
            this.trigger('select', model.get('id'));
          },
          removeAndSelect: function(model, collection, opts){
            if(this.length === 0){ // we've removed the final element in the collection
              this.trigger('select', -1); // set to an invalid model ID
              return;
            }
            
            var rmIdx = opts.index;
            var newIdx = rmIdx;
            if(rmIdx === this.length) // select an earlier model if the removed was the last one
              newIdx = rmIdx - 1;
            
            this.trigger('select', this.at(newIdx).get('id'));
          },
          comparators: {
            descend: {
              _byString: function(attr, reqA, reqB){
                var rawAttrA = reqA.get(attr).toLowerCase();
                var rawAttrB = reqB.get(attr).toLowerCase();
                
                var directionIndicator = rawAttrB.localeCompare(rawAttrA);
                if(directionIndicator === 0)
                  return directionIndicator;
                
                if(directionIndicator < 0)
                  return -1;
                
                return 1;
              },
              project: function(reqA, reqB){
                return this.comparators.descend._byString('project', reqA, reqB);
              },
              owner: function(reqA, reqB){
                return this.comparators.descend._byString('owner', reqA, reqB);
              },
              date: function(req){
                var rawDate = req.get('date');
                var date = new Date(rawDate);
                var order = (date.getFullYear() * 1e4) + ((date.getMonth()+1) * 1e2) + date.getDate();
                return -order;
              }
            },
            ascend: {
              _byString: function(attr, reqA, reqB){
                var rawAttrA = reqA.get(attr).toLowerCase();
                var rawAttrB = reqB.get(attr).toLowerCase();
                
                var directionIndicator = rawAttrA.localeCompare(rawAttrB);
                if(directionIndicator === 0)
                  return directionIndicator;
                
                if(directionIndicator < 0)
                  return -1;
                
                return 1;
              },
              project: function(reqA, reqB){ 
                return this.comparators.ascend._byString('project', reqA, reqB); 
              },
              owner: function(reqA, reqB){ 
                return this.comparators.ascend._byString('owner', reqA, reqB); 
              },
              date: function(req){
                var rawDate = req.get('date');
                var date = new Date(rawDate);
                var order = (date.getFullYear() * 1e4) + ((date.getMonth()+1) * 1e2) + date.getDate();
                return order;
              }
            }
          },
          // By default, this collection will sort by date, in descending order (i.e. newest first)
          comparator: function(model){
            return this.comparators.descend.date(model);
          },
          // Expands on the functionality of Backbone.Collection.sort(), with options to
          // specify the sort direction, and the attribute to sort on.
          directionalSort: function(direction, attr){
            this.comparator = this.comparators[direction][attr];
            this.sort();
          }
        });
        
        Backbone.View.prototype.close = function(){
          this.off();
          this.stopListening();
          this.undelegateEvents();
          if(this.onClose){
            this.onClose();
          }
        }
        
        var TableView = Backbone.View.extend({
          itemViews: [],
          initialize: function(opts){
            this.root = opts.root;
            
            this.listenTo(this.collection, "sort", this.render);
            this.listenTo(this.collection, 'select', this.render);   // when selected changes
            this.listenTo(this.collection, 'remove', this.render);   // when selected is deleted
            
            this.listenTo(this.root, 'action:new', this.renderFaded);
          },
          onClose: function(){
            this.closeItemViews();
          },
          closeItemViews: function(){
            // Invalidate our existing row views, since we are building new ones
            _.each(this.itemViews, function(itemView){ itemView.close(); });
            this.itemViews = [];          
          },
          events: {
            "click th": "toggleSort"
          },
          toggleSort: function(evt){
            // console.log(evt);
            var colHead = $(evt.target);
            var sortByAttribute = colHead.data('modelAttribute');
            var sortDirection = '';
            
            // Add a caret if the column isn't currently selected
            if(colHead.children('i.fa').length === 0){
              // Find the currently sorted column, and remove its caret
              var oldSortHead = this.$el.find('th > i.fa');
              oldSortHead.remove();
                            
              colHead.append("<i class='fa fa-caret-down'></i>");
              sortDirection = 'descend';
            }
            else {   // The column is already selected, swap the direction of the caret
              var sortIcon = colHead.children('i.fa');
              if(sortIcon.hasClass('fa-caret-up')){
                sortIcon.removeClass('fa-caret-up');
                sortIcon.addClass('fa-caret-down');
                sortDirection = 'descend';
              }
              else {
                sortIcon.removeClass('fa-caret-down');
                sortIcon.addClass('fa-caret-up');
                sortDirection = 'ascend';
              }
            }
            this.collection.directionalSort(sortDirection, sortByAttribute);
          },
          render: function(){
            // Invalidate our existing row views, since we are building new ones
            this.closeItemViews();
          
            this.$el.css('color', '');   // default to text color defined by CSS
            this.$el.find('tbody').empty();
            _.each(this.collection.models, this.renderItem, this);
            
            // Bold the currently selected element in the collection, if any
            var selectedIdx = this.collection.getSelectedIdx();
            if(selectedIdx !== -1){
              var selected = this.$el.find('tbody tr').get(selectedIdx);
              var $selected = $(selected);
              
              $selected.css('font-weight', 'bold');
            }
            
            return this;
          },
          renderFaded: function(){
            // Invalidate our existing row views, since we are building new ones
            this.closeItemViews();
          
            this.$el.css('color', '#ddd');   // override CSS text color, for 'disabled' effect
            this.$el.find('tbody').empty();
            _.each(this.collection.models, this.renderItem, this); // Note! This will do nothing when the collection is empty
            
            // this.$el.find('tbody tr').addClass('active');
            
            return this;
          },
          renderItem: function(item){
            var itemView = new TableItemView({ model: item });
            this.itemViews.push(itemView);
            itemView.render();
            this.$el.find('tbody').append(itemView.el);
          }
        });
        
        var TableItemView = Backbone.View.extend({
          tagName: 'tr',
          template: davemn.templates.table_item,
          months: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
          initialize: function(){
            this.listenTo(this.model, "change", this.render);
          },
          events: {
            "click": "viewClicked"
          },
          render: function(){
            // Pretty dates when from this year
            var modelAttrs = this.model.getAll();
            var modelDate = new Date(modelAttrs.date);
            
            if(modelDate.getFullYear() < 1970)   // force JS out of pre-Y2K behaviour; assume 21st century for dates 00-69
              modelDate.setFullYear(modelDate.getFullYear() + 100);
            if(modelDate.getFullYear() === <?php echo (new DateTime())->format('Y'); ?>)
              modelAttrs.date = this.months[modelDate.getMonth()] + ' ' + modelDate.getDate();
            
            this.$el.html(this.template(modelAttrs));
            return this;
          },
          viewClicked: function(){
            this.model.trigger('select', this.model.get('id'));
          }
        });
        
        var EditFormView = Backbone.View.extend({
          templateName: '',
          template: davemn.templates.form_base,
          mode: 'Edit',
          root: null,
          initialize: function(opts){
            this.root = opts.root;
            this.model = this.collection.getSelected();   // default to rendering the selected element (i.e. edit mode)
            if(this.model !== null)   // the page may load with no stored models, in which case we're not fetching the currently selected on init
              this.templateName = this.model.get('agency');
          
            this.listenTo(this.collection, 'select', this.renderSelected);   // when selected changes
            this.listenTo(this.collection, 'remove', this.renderSelected);   // when selected is deleted
            
            this.listenTo(this.root, 'action:save', this.save);
            this.listenTo(this.root, 'action:new', this.renderEmpty);
          },
          events: {
            'change select[name="agency"]': 'changeAgency'
          },
          changeAgency: function(evt){
            this.templateName = $(evt.target).val();
            this.render();
          },
          render: function(){ 
            if(this.model === null){   // in case render() is called directly, but there are no items to render
              this.model = new ChangeRequestModel();
              this.templateName = this.model.get('agency');
              this.mode = 'Add';
            }
          
            this.template = davemn.templates['form_' + this.templateName];
          
            var templateCtx = this.model.getAll();
            templateCtx.mode = this.mode;
            
            this.$el.html(this.template(templateCtx));

            // It's difficult to select radio buttons, and dropdowns using JSON+Handlebars alone.
            // Update these elements using jQuery instead.
            
            var areaVal = [this.model.get('area')];
            this.$el.find('input[name="area"]').val(areaVal);
            
            this.$el.find('textarea[name="project"]').val([this.model.get('project')]);
            this.$el.find('input[name="priority"]').val([this.model.get('priority')]);
            this.$el.find('select[name="status"]').val([this.model.get('status')]);
            this.$el.find('select[name="result"]').val([this.model.get('result')]);
            this.$el.find('select[name="category"]').val([this.model.get('category')]);
            
            return this;
          },
          renderSelected: function(){          
            this.model = this.collection.getSelected();
            if(this.model === null){
              this.renderEmpty();
              return;
            }
            this.templateName = this.model.get('agency');
            this.mode = 'Edit';
            this.render();
          },
          renderEmpty: function(){
            this.model = new ChangeRequestModel();
            this.templateName = this.model.get('agency');
            this.mode = 'Add';
            this.render();
          },
          formatExternalId: function(agency, extId){
            extId = extId.trim();
            extId = extId.replace(/[^0-9]/g,"");
            if(extId.length === 0)
              return '';
              
            switch(agency){
            default:
            case 'base':
              return 'REQ-' + extId;
              break;
            }
          },
          save: function(){
            // Convert values stored in form to Javascript object
            var newAttrs = {};
            $.each(this.$el.find('form').serializeArray(), function() {
              newAttrs[this.name] = this.value;
            });
            // Scrub external ID, if any, when agency is base.
            if(newAttrs['agency'] === 'base')  
              newAttrs['external_id'] = '';
            else
              newAttrs['external_id'] = this.formatExternalId(newAttrs['agency'], newAttrs['external_id']);
            
            var collection = this.collection;
            var root = this.root;
            var saveOpts = {
              success: function(model, resp){
                collection.add(model);
                // // Need to update the handle this view has to the currently selected
                // // model. 
                // this.renderSelected();
              },
              error: function(model, resp){
                var errObj = $.parseJSON(resp.responseText);
                console.log('An error occurred: ' + errObj.error);
              }
            };
            // jQuery's serializeArray() will ignore file input elements. Instead we provide the file upload
            // through an optional argument to Model.save().
            var attachments = this.$el.find('form input[name="attachment"]').get(0).files;
            if(attachments.length !== 0)
              saveOpts['attachment'] = attachments[0];
            
            this.model.save( newAttrs, saveOpts );
          }
        });
        
        var RootView = Backbone.View.extend({
          _initialCollection: null,
          tableEl: '',
          table: null,
          formEl: '',
          form: null,
          initialize: function(opts){
            this.listenTo(this.collection, "add", this.addToMasterCollection); // when an elem added to the current (temp) collection
            this.listenTo(this.collection, "remove", this.removeFromMasterCollection);
            
            this._initialCollection = this.collection.clone(); // defensive copy
            
            this.tableEl = opts.tableEl;
            this.table = new TableView({ el: this.tableEl, collection: this.collection, root: this });
            
            this.formEl = opts.formEl;
            this.form = new EditFormView({ el: this.formEl, collection: this.collection, root: this });
            
            $('a[href="#filter-all"]').click();
          },
          addToMasterCollection: function(model){
            this._initialCollection.add(model);
          },
          removeFromMasterCollection: function(model){
            this._initialCollection.remove(model);
          },
          events: {
            'click a[href^="#filter"]': 'filterByCategory',
            'click a[href^="#action"]': 'takeActionOnModel'
          },
          takeActionOnModel: function(evt){
            evt.preventDefault(); // prevent the user's browser from navigating to the URL fragment
            
            // Using currentTarget, since nested elements inside the <a> (like a Font Awesome <i>) 
            // may actually be responsible for the click event.
            var actionRaw = $(evt.currentTarget).attr('href').split('-');
            if(actionRaw.length < 2){
              console.log('Action "' + $(evt.currentTarget).attr('href') + '" not understood. No action taken.');
              return;
            }
            var action = actionRaw[1]; // 'new', 'save', ...
            switch(action){
            case 'new':
              this.trigger('action:new');
              break;
            case 'save':
              this.trigger('action:save');
              break;
            case 'delete':
              var toRm = this.collection.getSelected();
              if(toRm === null)
                break;
              
              // Only if we have something to delete
              toRm.destroy({
                collection: this.collection,
                // wait: true,   // TODO: NOT SURE IF NEEDED
                success: function(model, resp, opts){
                  opts.collection.remove(model);
                },
                error: function(model, resp, opts){
                  var errObj = $.parseJSON(resp.responseText);
                  console.log('An error occurred: ' + errObj.error);                  
                }
              });
              break;
            default:
            }
          },
          filterByCategory: function(evt){
            evt.preventDefault();
            
            var filterByRaw = $(evt.currentTarget).attr('href').split('-');
            if(filterByRaw.length < 2){
              console.log('Filter "' + $(evt.currentTarget).attr('href') + '" not understood. No filtering performed.');
              return;
            }
            var filterBy = filterByRaw[1]; // 'current', ...
            
            // Detach from and cleanup current collection before we replace it
            this.stopListening(this.collection);
            this.collection.close();
            
            switch(filterBy){
            default:
            // case 'trash': // do nothing for trash filter for now
            case 'all':
              // Never give a temporary view a handle into the master collection
              this.collection = this._initialCollection.clone();
              break;
            case 'current':
              var filteredModels = this._initialCollection.where({ category: 'Current' });
              this.collection = new ChangeRequestCollection(filteredModels);
              break;
            case 'archived':
              var filteredModels = this._initialCollection.where({ category: 'Archive' });
              this.collection = new ChangeRequestCollection(filteredModels);
              break;
            case 'future':
              var filteredModels = this._initialCollection.where({ category: 'Future' });
              this.collection = new ChangeRequestCollection(filteredModels);
              break;
            }

            // Reinstate our listeners on the new collection
            this.listenTo(this.collection, "add", this.addToMasterCollection);
            this.listenTo(this.collection, "remove", this.removeFromMasterCollection);
            
            // Cleanup our old views before we replace them
            this.table.close();
            this.form.close();
            
            this.table = new TableView({ el: this.tableEl, collection: this.collection, root: this });
            this.form = new EditFormView({ el: this.formEl, collection: this.collection, root: this });
            this.render();
          },
          render: function(){
            this.table.render();
            this.form.render();
          }
        });
        
        // Ala http://backbonejs.org/#FAQ-bootstrap
        var changes = new ChangeRequestCollection();
        var fetchedChanges = null;
        <?php
          $change_store = file_get_contents('rest/data.json');
          if($change_store !== false){
            echo "fetchedChanges = $change_store;";
          }
        ?>
        
        if(fetchedChanges !== null && fetchedChanges.length > 0)
          changes.reset(fetchedChanges);
        
        var root = new RootView({
          el: "body",
          tableEl: "table#change-table",
          formEl: "#col-details",
          collection: changes
        });
        root.render();
      });
    </script>
  </body>
</html>
