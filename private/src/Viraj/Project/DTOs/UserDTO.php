<?php
declare(strict_types=1);

/*
 * 420DW3_07278_Project UserDTO.php
 *
 * @author Viraj Patel
 * @since 2024-03-28
 */

namespace Viraj\Project\DTOs;

use DateTime;
use Exception;
use Teacher\GivenCode\Exceptions\RuntimeException;
use Teacher\GivenCode\Exceptions\ValidationException;
use Viraj\Project\DAOs\UsersDAO;

/**
 * User DTO-type class
 */
class UserDTO {
    
    // Class constants.
    
    /**
     * Database table name for this DTO.
     * @const
     */
    public const TABLE_NAME = "users";
    
    /**
     * Username maximum length.
     * @const
     */
    public const USERNAME_MAX_LENGTH = 64;
    
    /**
     * Password maximum length.
     * @const
     */
    public const PASSWORD_HASH_MAX_LENGTH = 72;
    
    /**
     * Email maximum length.
     * @const
     */
    public const EMAIL_MAX_LENGTH = 256;
    
    // Class Properties
    private int $id;
    private string $username;
    private string $passwordHash;
    private string $email;
    private ?DateTime $creationDate = null;
    private ?DateTime $lastModificationDate = null;
    
    /**
     * Array of permission associated with this user.
     * @var PermissionDTO[]
     */
    private array $permissions = [];
    
    /**
     * Constructor
     */
    public function __construct() {}
    
    /**
     * Static constructor-like function to create instances of UserDTO without an id or temporal management
     * attribute values. Used to create instances before inserting them in the database.
     *
     * @static
     * @param string $username     The initial value for the username property.
     * @param string $passwordHash The initial value for the passwordHash property.
     * @param string $email        The initial value for the email property.
     * @return UserDTO The created instance of UserDTO.
     * @throws ValidationException ValidationException is thrown when setting the passed arguments as property values.
     */
    public static function fromValues(string $username, string $passwordHash, string $email) : UserDTO {
        
        $object = new UserDTO();
        
        // Set the property values from the parameters.
        // Using the setter methods allows me to validate the values on the spot.
        $object->setUsername($username);
        $object->setPasswordHash($passwordHash);
        $object->setEmail($email);
        
        // return the created instance
        return $object;
    }
    
    /**
     * Static constructor-like function to create instances of UserDTO with an id and temporal management
     * attribute values. Used to create instances from database-fetched arrays.
     *
     * @static
     * @param array $dbAssocArray The associative array of a fetched record of an UserDTO entity from the database.
     * @return UserDTO The created instance of UserGroupDTO.
     * @throws ValidationException If an error occurs during setting the object properties.
     */
    public static function fromDbArray(array $dbAssocArray) : UserDTO {
        
        self::validateDbArray($dbAssocArray);
        
        $object = new UserDTO();
        
        // Set the property values from the array parameter
        $object->setId((int) $dbAssocArray["id"]);
        $object->setUsername($dbAssocArray["username"]);
        $object->setPasswordHash($dbAssocArray["password_hash"]);
        $object->setEmail($dbAssocArray["email"]);
        
        // conversion from DB-formatted datetime strings back into DateTime objects.
        $object->setCreationDate(
            DateTime::createFromFormat(DB_DATETIME_FORMAT, $dbAssocArray["created_at"])
        );
        
        if (!empty($dbAssocArray["last_modified_at"])) {
            $object->setLastModificationDate(DateTime::createFromFormat(DB_DATETIME_FORMAT, $dbAssocArray["last_modified_at"]));
        }
        
        // return the created instance
        return $object;
    }
    
    /**
     * Validating the array which we retrieved from the database.
     *
     * @throws ValidationException If array doesn't require data/ properties or if invalid data retrieve from the database.
     */
    private static function validateDbArray(array $dbArray) : void {
        
        if (empty($dbArray["id"])) {
            throw new ValidationException("Record array does not contain an [id] field. Check column names.", 500);
        }
        if (!is_numeric($dbArray["id"])) {
            throw new ValidationException("Record array [id] field is not numeric. Check column types.", 500);
        }
        if (empty($dbArray["username"])) {
            throw new ValidationException("Record array does not contain an [username] field. Check column names.", 500);
        }
        if (empty($dbArray["password_hash"])) {
            throw new ValidationException("Record array does not contain an [password_hash] field. Check column names.", 500);
        }
        if (empty($dbArray["created_at"])) {
            throw new ValidationException("Record array does not contain an [created_at] field. Check column names.", 500);
        }
        if (DateTime::createFromFormat(DB_DATETIME_FORMAT, $dbArray["created_at"]) === false) {
            throw new ValidationException("Failed to parse [created_at] field as DateTime. Check column types.", 500);
        }
        if (!empty($dbArray["last_modified_at"]) &&
            (DateTime::createFromFormat(DB_DATETIME_FORMAT, $dbArray["last_modified_at"]) === false)
        ) {
            throw new ValidationException("Failed to parse [last_modified_at] field as DateTime. Check column types.", 500);
        }
    }
    
    /**
     * Get the Table name.
     *
     * @return string The name of the database table associated with this DTO.
     */
    public function getDatabaseTableName() : string {
        return self::TABLE_NAME;
    }
    
    /**
     * Getter for <code>Id</code>
     *
     * @return int
     */
    public function getId() : int {
        return $this->id;
    }
    
    /**
     * Setter for <code>Id</code>
     *
     * @param int $id
     * @throws ValidationException If the id is lower than 1.
     */
    public function setId(int $id) : void {
        if ($id < 1) {
            throw new ValidationException("Id value cannot be inferior to 1.");
        }
        $this->id = $id;
    }
    
    /**
     *  Getter for <code>Username</code>.
     *
     * @return string The username of the user.
     */
    public function getUsername() : string {
        return $this->username;
    }
    
    /**
     * Setter for <code>Username</code>.
     *
     * @param string $username The username to set.
     * @throws ValidationException If the username exceeds the maximum length.
     */
    public function setUsername(string $username) : void {
        if (mb_strlen($username) > self::USERNAME_MAX_LENGTH) {
            throw new ValidationException("Username length must not be longer than " . self::USERNAME_MAX_LENGTH . ".");
        }
        $this->username = $username;
    }
    
    /**
     * Getter for <code>PasswordHash</code>.
     *
     * @return string The hashed password of the user.
     */
    public function getPasswordHash() : string {
        return $this->passwordHash;
    }
    
    /**
     * Setter for <code>PasswordHash</code>.
     *
     * @param string $passwordHash The hashed password to set.
     * @throws ValidationException If the password hash exceeds the maximum length.
     */
    public function setPasswordHash(string $passwordHash) : void {
        if (mb_strlen($passwordHash) > self::PASSWORD_HASH_MAX_LENGTH) {
            throw new ValidationException("Password hash length must not be longer than " . self::PASSWORD_HASH_MAX_LENGTH . ".");
        }
        $this->passwordHash = $passwordHash;
    }
    
    /**
     * Getter for <code>Email</code>.
     *
     * @return string The email address of the user.
     */
    public function getEmail() : string {
        return $this->email;
    }
    
    /**
     * Setter for <code>Email</code>.
     *
     * @param string $email The email address to set.
     * @throws ValidationException If the email exceeds the maximum length.
     */
    public function setEmail(string $email) : void {
        if (mb_strlen($email) > self::EMAIL_MAX_LENGTH) {
            throw new ValidationException("Email hash length must not be longer than " . self::PASSWORD_HASH_MAX_LENGTH . ".");
        }
        $this->email = $email;
    }
    
    /**
     * Getter for <code>CreationDate</code>.
     *
     * @return DateTime|null The creation date of the user or null if not set.
     */
    public function getCreationDate() : ?DateTime {
        return $this->creationDate;
    }
    
    /**
     * Setter for <code>CreationDate</code>.
     *
     * @param DateTime|null $creationDate The creation date to set.
     */
    public function setCreationDate(?DateTime $creationDate) : void {
        $this->creationDate = $creationDate;
    }
    
    /**
     * Getter for <code>LastModificationDate</code>.
     *
     * @return DateTime|null The last modification date of the user or null if not set.
     */
    public function getLastModificationDate() : ?DateTime {
        return $this->lastModificationDate;
    }
    
    /**
     * Setter for <code>LastModificationDate</code>.
     *
     * @param DateTime|null $lastModificationDate The last modification date to set.
     */
    public function setLastModificationDate(?DateTime $lastModificationDate) : void {
        $this->lastModificationDate = $lastModificationDate;
    }
    
    /**
     * Validates the instance for creation of its record in the database.
     *
     * @param bool $optThrowExceptions [OPTIONAL] Whether to throw exceptions or not if invalid. Defaults to true.
     * @return bool <code>True</code> if valid, <code>false</code> otherwise.
     * @throws ValidationException If the instance is invalid and the <code>$optThrowExceptions</code> parameter is <code>true</code>.
     */
    public function validateForDbCreation(bool $optThrowExceptions = true) : bool {
        // ID must not be set
        if (!empty($this->id)) {
            if ($optThrowExceptions) {
                throw new ValidationException("UserDTO is not valid for DB creation: ID value already set.");
            }
            return false;
        }
        // username is required
        if (empty($this->username)) {
            if ($optThrowExceptions) {
                throw new ValidationException("UserDTO is not valid for DB creation: dayOfTheWeek value not set.");
            }
            return false;
        }
        // passwordHash is required
        if (empty($this->passwordHash)) {
            if ($optThrowExceptions) {
                throw new ValidationException("UserDTO is not valid for DB creation: passwordHash value not set.");
            }
            return false;
        }
        // email is required
        if (empty($this->email)) {
            if ($optThrowExceptions) {
                throw new ValidationException("UserDTO is not valid for DB creation: email value not set.");
            }
            return false;
        }
        // creationDate must not be set
        if (!is_null($this->creationDate)) {
            if ($optThrowExceptions) {
                throw new ValidationException("UserDTO is not valid for DB creation: creationDateTime value already set.");
            }
            return false;
        }
        // lastModification must not be set
        if (!is_null($this->lastModificationDate)) {
            if ($optThrowExceptions) {
                throw new ValidationException("UserDTO is not valid for DB creation: lastModificationDate value already set.");
            }
            return false;
        }
        
        return true;
    }
    
    /**
     * Validates the instance for the update of its record in the database.
     *
     * @param bool $optThrowExceptions [OPTIONAL] Whether to throw exceptions or not if invalid. Defaults to true.
     * @return bool <code>True</code> if valid, <code>false</code> otherwise.
     * @throws ValidationException If the instance is invalid and the <code>$optThrowExceptions</code> parameter is <code>true</code>.
     */
    public function validateForDbUpdate(bool $optThrowExceptions = true) : bool {
        // ID is required
        if (empty($this->id)) {
            if ($optThrowExceptions) {
                throw new ValidationException("UserDTO is not valid for DB updation: ID value is not set.");
            }
            return false;
        }
        // username is required
        if (empty($this->username)) {
            if ($optThrowExceptions) {
                throw new ValidationException("UserDTO is not valid for DB updation: dayOfTheWeek value not set.");
            }
            return false;
        }
        // passwordHash is required
        if (empty($this->passwordHash)) {
            if ($optThrowExceptions) {
                throw new ValidationException("UserDTO is not valid for DB updation: passwordHash value not set.");
            }
            return false;
        }
        // email is required
        if (empty($this->email)) {
            if ($optThrowExceptions) {
                throw new ValidationException("UserDTO is not valid for DB updation: email value not set.");
            }
            return false;
        }
        return true;
    }
    
    /**
     * Validates the instance for the deletion of its record in the database.
     *
     * @param bool $optThrowExceptions [OPTIONAL] Whether to throw exceptions or not if invalid. Defaults to true.
     * @return bool <code>True</code> if valid, <code>false</code> otherwise.
     * @throws ValidationException If the instance is invalid and the <code>$optThrowExceptions</code> parameter is <code>true</code>.
     */
    public function validateForDbDelete(bool $optThrowExceptions = true) : bool {
        // ID is required
        if (empty($this->id)) {
            if ($optThrowExceptions) {
                throw new ValidationException("UserDTO is not valid for DB deletion: ID value is not set.");
            }
            return false;
        }
        return true;
    }
    
    /**
     * Retrieves permissions associated with the user.
     *
     * @param bool $forceReload [Optional] If set to <code>true</code>, forces the reload of the permission records from the database. Defaults to <code>true</code>.
     * @return array An array of PermissionDTO objects representing the permissions associated with the user.
     * @throws RuntimeException If there is an issue with loading the permission records.
     */
    public function getPermissions(bool $forceReload = true) : array {
        try {
            // If the permissions array is empty or forceReload is set to true, reload permissions from the database.
            if (empty($this->permissions) || $forceReload) {
                $this->loadPermissions();
            }
        } catch (Exception $exception) {
            // If an exception occurs during the loading of permissions, throw a RuntimeException.
            throw new RuntimeException("Failed to load permission entity records for user id# [$this->id].", $exception->getCode(), $exception);
        }
        
        return $this->permissions;
    }
    
    /**
     * Loads permissions associated with the user from the database.
     *
     * @return void
     * @throws RuntimeException If there is an issue with loading the permissions.
     * @throws ValidationException If there is an issue with the validation of the retrieved data.
     */
    public function loadPermissions() : void {
        $users_dao = new UsersDAO();
        $this->permissions = $users_dao->getPermissionsByUser($this);
    }
    
    /**
     * Converting UserDTO object into JSON array.
     *
     * @return array
     */
    public function toArray() : array {
        $array = [
            "id" => $this->getId(),
            "username" => $this->getUsername(),
            "passwordHash" => $this->getPasswordHash(),
            "email" => $this->getEmail(),
            "creationDate" => $this->getCreationDate()?->format(HTML_DATETIME_FORMAT),
            "lastModificationDate" => $this->getLastModificationDate()?->format(HTML_DATETIME_FORMAT),
            "permissions" => []
        ];
        
        foreach ($this->permissions as $permission) {
            $array["permissions"][$permission->getId()] = $permission->toArray();
        }
        
        return $array;
    }
}