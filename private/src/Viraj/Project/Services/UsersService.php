<?php
declare(strict_types=1);

/*
 * 420DW3_07278_Project UsersService.php
 *
 * This file defines the UsersService class, responsible for managing user-related operations.
 * It interacts with the UsersDAO class to perform CRUD operations on user data.
 *
 * This file contains the UsersService class, which provides methods for interacting with user.
 * It handles CRUD operations for user, utilizing the UsersDAO class for database interaction.
 *
 * @author Viraj Patel
 * @since 2024-04-02
 */

namespace Viraj\Project\Services;

use Exception;
use Teacher\GivenCode\Exceptions\RuntimeException;
use Teacher\GivenCode\Exceptions\ValidationException;
use Viraj\Project\DAOs\UserPermissionDAO;
use Viraj\Project\DTOs\UserDTO;
use Viraj\Project\DAOs\UsersDAO;

/**
 * Service class for users operation.
 */
class UsersService {
    
    // Class properties
    private UsersDAO $usersDAO; // UsersDAO object for interacting with the users table of the database.
    private CryptographyService $cryptographyService; // CryptographyService object for hashing passwords.
    private UserPermissionDAO $userPermissionDAO; // UserPermissionDAO object for interacting with the user_permissions table of the database.
    
    /**
     * Constructor for UsersService class.
     * Initializes UsersDAO, CryptographyService and UserPermissionDAO objects for database interaction and CryptographyService object the hashing and validation of user passwords.
     */
    public function __construct() {
        $this->usersDAO = new UsersDAO(); // Initialize UsersDAO object.
        $this->cryptographyService = new CryptographyService(); // Initialize CryptographyService object.
        $this->userPermissionDAO = new UserPermissionDAO(); // Initialize UserPermissionDAO object.
    }
    
    /**
     * Retrieves all users from the database.
     *
     * @return UserDTO[] An array of UserDTO objects representing users.
     * @throws ValidationException If validation of retrieved data fails.
     * @throws RuntimeException If a database connection error occurs.
     */
    public function getAllUsers() : array {
        return $this->usersDAO->getAll();
    }
    
    
    /**
     * Retrieves a user with associated permissions by their ID from the database.
     *
     * @param int $id The ID of the user to retrieve.
     * @return UserDTO|null The UserDTO object representing the user, or null if not found.
     * @throws RuntimeException If a database connection error occurs if no record is found for the given ID.
     * @throws ValidationException If validation of retrieved data fails.
     */
    public function getUserById(int $id) : ?UserDTO {
        // return $this->usersDAO->getById($id);
        $user = $this->usersDAO->getById($id);
        $user?->loadPermissions();
        return $user;
    }
    
    /**
     * Creates a new user in the database.
     *
     * @param string $username The username of the new user.
     * @param string $password The password of the new user.
     * @param string $email    The email of the new user.
     * @return UserDTO The UserDTO object representing the newly created user.
     * @throws RuntimeException If failure to create and insert new user into the database.
     */
    public function createUser(string $username, string $password, string $email, array $permissions) : UserDTO {
        
        try {
            $hash_password = $this->cryptographyService->hashPassword($password); // Hash the password.
            $user = UserDTO::fromValues($username, $hash_password, $email);
            
            $user = $this->usersDAO->create($user);
            $this->userPermissionDAO->createManyForUser($user->getId(), $permissions);
            
            return $this->getUserById($user->getId());
        } catch (Exception $exception) {
            throw new RuntimeException("Failure to create user [$username, $email]." ,(int) $exception->getCode(), $exception);
        }
        
    }
    
    /**
     * Updates an existing user in the database.
     *
     * @param int    $id       The ID of the user to update.
     * @param string $username The new username for the user.
     * @param string $password The new password for the user.
     * @param string $email    The new email for the user.
     * @return UserDTO The UserDTO object representing the updated user.
     * @throws RuntimeException If failure to update user into the database.
     */
    public function updateUser(int $id, string $username, string $password, string $email, array $permissions) : UserDTO {
        
        try {
            
            $connection = DBConnectionService::getConnection(); // Get database connection.
            $connection->beginTransaction(); // Begin database transaction.
            
            try {
                $user = $this->usersDAO->getById($id); // Retrieve the user by ID.
                
                // Checking whether $user is null or not.
                if (is_null($user)) {
                    throw new Exception("User id# [$id] not found in the database.");
                }
                
                $user->setUsername($username); // Set the new username.
                
                $hash_password = $this->cryptographyService->hashPassword($password); // Hash the new password.
                
                $user->setPasswordHash($hash_password); // Set the hashed password.
                
                $user->setEmail($email); // Set the new email.
                
                $result = $this->usersDAO->update($user); // Update the user in the database.
                
                // Removing all old permissions.
                $this->userPermissionDAO->deleteAllByUserId($result->getId());
                
                // Adding all new permisssions.
                if (!empty($permissions)) {
                    $this->userPermissionDAO->createManyForUser($result->getId(), $permissions);
                }
                //$this->userPermissionDAO->createManyForUser($result->getId(), $permissions);
                
                $connection->commit(); // Commit the transaction to save changes.
                
                return $this->getUserById($result->getId()); // Return the updated user.
                
            } catch (Exception $inner_exception) {
                $connection->rollBack();
                throw $inner_exception;
            }
            
        } catch (Exception $exception) {
            throw new RuntimeException("Failure to update user id#[$id].", (int) $exception->getCode(), $exception);
        }
        
    }
    
    /**
     * Deletes a user by their ID from the database.
     *
     * @param int $id The ID of the user to delete.
     * @return void
     * @throws RuntimeException If failure to delete user from the database.
     */
    public function deleteUser(int $id) : void {
        
        try {
            
            $connection = DBConnectionService::getConnection(); // Get database connection.
            $connection->beginTransaction(); // Begin database transaction.
            
            try {
                $user = $this->usersDAO->getById($id); // Retrieve the user by ID.
                
                // Checking whether $user is null or not.
                if (is_null($user)) {
                    throw new Exception("User id# [$id] not found in the database.");
                }
                
                // Delete the first the user and permissions association from the `user_permissions` table.
                $this->userPermissionDAO->deleteAllByUserId($user->getId());
                
                $this->usersDAO->delete($user); // Delete the user from the database.
                
                $connection->commit(); // Commit the transaction to save changes.
                
            } catch (Exception $inner_exception) {
                $connection->rollBack();
                throw $inner_exception;
            }
            
        } catch (Exception $exception) {
            throw new RuntimeException("Failure to delete user id# [$id].", (int) $exception->getCode(), $exception);
        }
        
    }
    
    
    /**
     * Retrieves permissions associated with a user by their ID from the database.
     *
     * @param int $id The ID of the user.
     * @return array An array of PermissionDTO objects representing the permissions associated with the user.
     * @throws RuntimeException If a database connection error occurs.
     * @throws ValidationException If there's an issue with the validation of the retrieved data.
     */
    public function getUserPermissionByUserId(int $id) : array {
        return $this->usersDAO->getPermissionsByUserId($id);
    }
    
    
    /**
     * Retrieves permissions associated with a user from the database.
     *
     * @param UserDTO $user The UserDTO object representing the user.
     * @return array An array of PermissionDTO objects representing the permissions associated with the user.
     * @throws RuntimeException If a database connection error occurs.
     * @throws ValidationException If the user object does not have an ID set.
     */
    public function getUserPermission(UserDTO $user) : array {
        return $this->getUserPermissionByUserId($user->getId());
    }
    
    /**
     * Validates a user's credentials.
     *
     * @param string $username The username of the user.
     * @param string $password The password of the user.
     * @return UserDTO|null|false The UserDTO object if credentials are valid, null if username is not found, false if password is invalid.
     * @throws RuntimeException If a database connection error occurs.
     * @throws ValidationException If there's an issue with the validation of the retrieved data.
     */
    public function validateUser(string $username, string $password) : UserDTO|null|false {
        
        // Retrieve user by username from the database.
        $user = $this->usersDAO->getUserByUsername($username);
        
        // If no user found with the provided username, return null.
        if (is_null($user)) {
            return null;
        }
        
        // Compare the provided password with the hashed password stored in the database.
        $result = $this->cryptographyService->comparePassword($password, $user->getPasswordHash());
        
        // If passwords don't match, return false.
        if ($result === false) {
            return false;
        }
        
        // If credentials are valid, return the UserDTO object.
        return $user;
    }
}