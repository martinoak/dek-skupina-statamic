# Custom Forms for Statamic v2.x

by Miloslav Koštíř (2020)

- Stores user input (forms) in database
- Allows to configure custom submission callbacks (EventListener)

## Instalation
- Copy or clone this repo to your `site/addons/CustomForms`  
`git clone https://github.com/miloslavkostir/statamicv2-custom-forms.git CustomForms`

## Settings
You can configure addon:

### Datetime format
The [PHP date format string](https://www.php.net/manual/en/function.date.php). Default is global Date format (see Settings -> System).
Determines date format in submissions list and details

### Replace native Forms
Native Forms section in left menu will be hidden. All buttons (Configure, Export) is available in Custom Forms.

### Callbacks
You can define custom callback for each form. Syntax:   
```yaml
form_name:
  api: MyAddon
  method: methodName
```
Configuration above will call: 
```php
$this->api('MyAddon')->methodName($submission)  // $submission is Statamic\Forms\Submission
```
Of course, you must have class `Statamic\Addons\MyAddon\MyAddonAPI` with method `methodName` see [statamic doc](https://docs.statamic.com/addons/classes/api)

### Database storage
Enables/Disables database store.    
You can define storage rules independent of formset settings. In formset settings you define storing to flat files (native behaviour) and here in Database storage you explicitly define storing in DB.   
Possible value:
- According to formset's setings: (default) DB store corresponds to formset settings 
- true: each submission is stored to DB independently of the formset settings
- false: nothing is stored to DB independently of the formset settings

Finally you can store submission whenever you want with helper method `saveSubmission`.

## Helper methods
`Statamic\Addons\CustomForms\CustomFormsAPI::saveSubmission(Statamic\Forms\Submission $submission): void;`
```php
$this->api('CustomForms')->saveSubmission($submission)  // $submission is Statamic\Forms\Submission
```

`Statamic\Addons\CustomForms\CustomFormsAPI::getFormName(Statamic\Forms\Submission $submission): string;`
```php
$formName = $this->api('CustomForms')->getFormName($submission)  // $submission is Statamic\Forms\Submission
```
