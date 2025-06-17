<?php
/**
 * this is table layer. what so called Data access layer
 * classes in this layer shall be extended from CRUDTable or Gemvc\Core\Table ;
 * for each column in database table, you must define property in this class with same name and property type;
 */
namespace App\Table;

use Gemvc\Database\Table;
use Gemvc\Database\Schema;

/**
 * User table class for handling User database operations
 * 
 * @property int $id User's unique identifier column id in database table
 * @property string $name User's name column name in database table
 * @property string $description User's description column description in database table
 */
class UserTable extends Table
{
    public int $id;
    public string $name;
    public string $email;
    public ?string $description;
    //password is not shown in the result of the select query , but this property can be setted trough setPassword() method in the UserModel class
    protected string $password;

    protected array $_type_map = [
        'id' => 'int',
        'name' => 'string',
        'email' => 'string',
        'description' => 'string',
        'password' => 'string',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->description = null;
    }


    /**
     * @return string
     * the name of the database table
     */
    public function getTable(): string
    {
        //return the name of the table in database
        return 'users';
    }

    /**
     * @return array
     * the schema of the table in database and its relations
     */
    public function defineSchema(): array
    {
        return [
            Schema::index('email'),
            Schema::unique('email'),
            Schema::index('description')
        ];
    }

    /**
     * @return null|static
     * null or UserTable Object
     */
    public function selectById(int $id): null|static
    {
        $result = $this->select()->where('id', $id)->limit(1)->run();
        return $result[0] ?? null;
    }

    /**
     * @return null|static[]
     * null or array of UserTable Objects
     */
    public function selectByName(string $name): null|array
    {
        return $this->select()->whereLike('name', $name)->run();
    }

    public function selectByEmail(string $email): null|static
    {
        $arr = $this->select()->where('email', $email)->limit(1)->run();
        return $arr[0] ?? null;
    }
} 