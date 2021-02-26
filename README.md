# class-laravel-dataTable
PHP 8 vs Laravel 8 class data table full option model.
## How to use
  ```
  use App\Enums\UserDelete;<br/>
  use App\LaravelDatatableBackEnd;<br/>
  Class ... </br>
  public function ajaxMetal(Request $request): \Illuminate\Http\Response|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory </br>
  {
    if (!$request->ajax())
            abort(404);
  /*
  *  @param 1*: request
  *  @param 2*: Name class model in folder App\Models
  *  @param 3*: Table name
  *  @param 4: array columns search
  *  @param 5: array columns filter
  *  @param 6: array columns concat only 2 items
  *  @param 7: array face_search
  *  @param 8: array withs
  */
    $data = new LaravelDatatableBackEnd(
            $request, 
            'User', 
            'User',
            ['email'],
            ['delete_flg' => UserDelete::NotDelete],
            ['first_name','last_name],
            ['name','first_name'],
            ['hoppies']
        );
     $response = $data->outObject();
     return response($response);
  }
  ...
  ```
