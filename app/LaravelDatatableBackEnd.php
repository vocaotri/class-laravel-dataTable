<?php


namespace App;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use InvalidArgumentException;
use JetBrains\PhpStorm\ArrayShape;

/**
 * Class LaravelDatatableBackEnd
 * @package App
 */
class LaravelDatatableBackEnd
{
    /**
     * LaravelDatatableBackEnd constructor.
     * @param Request $request
     * @param string $model
     * @param string $tableName
     * @param array $columnsSearch
     * @param array $columnsFilter
     * @param array $columnsConcat
     * @param array $columnsFace
     * @param array $columnsWith
     */
    public function __construct(
        private Request $request,
        private string $model,
        private string $tableName,
        private array $columnsSearch = [],
        private array $columnsFilter = [],
        private array $columnsConcat = [],
        private array $columnsFace = [],
        private array $columnsWith = [],
    )
    {
        $this->validate();
    }

    /**
     * @param string $model
     */
    public function setModel(string $model): void
    {
        $this->model = $model;
    }


    /**
     * @return array
     */
    #[ArrayShape(["draw" => "int", "iTotalRecords" => "mixed", "iTotalDisplayRecords" => "mixed", "aaData" => "mixed"])] public function outObject(): array
    {
        $draw = $this->request->get('draw');
        $start = $this->request->get("start");
        $rowperpage = $this->request->get("length"); // Rows display per page

        $columnIndex_arr = $this->request->get('order');
        $columnName_arr = $this->request->get('columns');
        $order_arr = $this->request->get('order');
        $search_arr = $this->request->get('search');

        $columnIndex = $columnIndex_arr[0]['column']; // Column index
        $columnName = $columnName_arr[$columnIndex]['data'] ?? 'id'; // Column name
        $columnSortOrder = $order_arr[0]['dir'] ?? 'desc'; // asc or desc
        $searchValue = $search_arr['value']; // Search value
        // init database before query
        if (isset($this->columnsFace[0]) && $columnName === $this->columnsFace[0]) $columnName = $this->columnsFace[1];
        $arrayColumnsSearch = $this->columnsSearch;
        $arrayFilter = $this->columnsFilter;
        $arrayConcat = $this->columnsConcat;
        $arrayWiths = $this->columnsWith;
        $this->autoIndex($arrayColumnsSearch, $arrayConcat);

        $records = $this->model::select('*');
        // Total records
        $totalRecords = $records->count();
        $records->where(function ($q) use ($arrayFilter) {
            foreach ($arrayFilter as $namecolumnsFilter => $valuecolumnsFilter)
                $q->where($namecolumnsFilter, $valuecolumnsFilter);
        })->where(function ($q) use ($arrayColumnsSearch, $arrayConcat, $searchValue) {
            foreach ($arrayColumnsSearch as $column)
                $q->orWhere($column, 'like', '%' . $searchValue . '%');
            if (!empty($arrayConcat))
                $q->orWhere(DB::raw("CONCAT(`$arrayConcat[0]`, '', `$arrayConcat[1]`)"), 'LIKE', "%" . $searchValue . "%");
        });
        // Fetch records
        $totalRecordsWithFilter = $records->count();
        if (!empty($arrayWiths))
            $records = $records->with($arrayWiths);
        $records = $records->skip($start)
            ->take($rowperpage)
            ->orderBy($columnName, $columnSortOrder)
            ->get();
        return array(
            "draw" => intval($draw),
            "iTotalRecords" => $totalRecords,
            "iTotalDisplayRecords" => $totalRecordsWithFilter,
            "aaData" => $records->toArray(),
        );
    }

    /**
     * @param array $arrayColumnsSearch
     * @param array $arrayConcat
     */
    private function autoIndex(array $arrayColumnsSearch = [], array $arrayConcat = []): void
    {

        try {
            $collection = collect(DB::select("SHOW INDEXES FROM " . $this->tableName))->pluck('Column_name')->toArray();
        } catch (\Exception $e) {
            $collection = collect(DB::raw("SHOW INDEXES FROM " . $this->tableName))->pluck('Column_name')->toArray();
        };
        $nameLower = strtolower($this->tableName);
        foreach ($arrayColumnsSearch as $indexColumn) {
            if (!in_array($indexColumn, $collection)) {
                try {
                    DB::select("ALTER TABLE $this->tableName ADD  FULLTEXT " . $nameLower . "_$indexColumn ($indexColumn)");
                } catch (\Exception $e) {
                    DB::raw("ALTER TABLE $this->tableName ADD  FULLTEXT " . $nameLower . "_$indexColumn ($indexColumn)");
                }
            }
        }
        foreach ($arrayConcat as $indexColumnsConcat) {
            if (!in_array($indexColumnsConcat, $collection)) {
                try {
                    DB::select("ALTER TABLE $this->tableName ADD  FULLTEXT " . $nameLower . "_$indexColumnsConcat ($indexColumnsConcat)");
                } catch (\Exception $e) {
                    DB::raw("ALTER TABLE $this->tableName ADD  FULLTEXT " . $nameLower . "_$indexColumnsConcat ($indexColumnsConcat)");
                }
            }
        }
    }

    /**
     *
     */
    private function validate(): void
    {
        $message = "";
        $model = 'App\\Models\\' . Str::studly(Str::singular($this->model));
        if (!is_subclass_of($model, 'Illuminate\Database\Eloquent\Model') && !is_subclass_of($model, 'Jenssegers\Mongodb\Eloquent\Model') && $model !== null) {
            $message .= " Model not found,";
        }
        if (!Schema::hasTable($this->tableName)) {
            $message .= "table $this->tableName not found,";
        }
        if (!empty($message)) {
            throw new InvalidArgumentException(substr($message, 0, -1));
        }
        $this->setModel('App\\Models\\' . $this->model);
    }
}
