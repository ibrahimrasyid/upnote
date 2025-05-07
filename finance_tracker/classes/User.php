<?php
require_once __DIR__ . '/../config/database.php';

class User {
    private $userId;
    private $name;
    private $email;
    private $password;
    
    // Constructor
    public function __construct($userId = null) {
        if ($userId) {
            $this->userId = $userId;
            $this->loadUser();
        }
    }
    
    // Load user data from database
    private function loadUser() {
        $sql = "SELECT * FROM USER WHERE User_ID = $this->userId";
        $result = fetch_row($sql);
        
        if ($result) {
            $this->name = $result['Name'];
            $this->email = $result['Email'];
            // Don't load password for security
        }
    }
    
    // Getters
    public function getId() {
        return $this->userId;
    }
    
    public function getName() {
        return $this->name;
    }
    
    public function getEmail() {
        return $this->email;
    }
    
    // Register a new user
    public static function register($name, $email, $password) {
        global $conn;
        
        // Check if email already exists
        $sql = "SELECT * FROM USER WHERE Email = '$email'";
        $result = execute_query($sql);
        
        if ($result->num_rows > 0) {
            return [
                'success' => false,
                'message' => 'Email already exists. Please use a different email.'
            ];
        }
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new user
        $sql = "INSERT INTO USER (Name, Email, Password) VALUES ('$name', '$email', '$hashedPassword')";
        
        if (execute_query($sql)) {
            $userId = last_insert_id();
            return [
                'success' => true,
                'message' => 'Registration successful. Please login.',
                'user_id' => $userId
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Registration failed. Please try again.'
            ];
        }
    }
    
    // Login a user
    public static function login($email, $password) {
        // Get user by email
        $sql = "SELECT * FROM USER WHERE Email = '$email'";
        $user = fetch_row($sql);
        
        if (!$user) {
            return [
                'success' => false,
                'message' => 'Invalid email or password.'
            ];
        }
        
        // Verify password
        if (password_verify($password, $user['Password'])) {
            // Start session and store user data
            $_SESSION['user_id'] = $user['User_ID'];
            $_SESSION['user_name'] = $user['Name'];
            $_SESSION['user_email'] = $user['Email'];
            
            return [
                'success' => true,
                'message' => 'Login successful.',
                'user_id' => $user['User_ID']
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Invalid email or password.'
            ];
        }
    }
    
    // Logout a user
    public static function logout() {
        // Unset all session variables
        $_SESSION = [];
        
        // Destroy the session
        session_destroy();
        
        return [
            'success' => true,
            'message' => 'Logout successful.'
        ];
    }
    
    // Update user profile
    public function updateProfile($name, $email) {
        // Check if email already exists for other users
        $sql = "SELECT * FROM USER WHERE Email = '$email' AND User_ID != $this->userId";
        $result = execute_query($sql);
        
        if ($result->num_rows > 0) {
            return [
                'success' => false,
                'message' => 'Email already exists. Please use a different email.'
            ];
        }
        
        // Update user
        $sql = "UPDATE USER SET Name = '$name', Email = '$email' WHERE User_ID = $this->userId";
        
        if (execute_query($sql)) {
            // Update session data
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            
            // Update object properties
            $this->name = $name;
            $this->email = $email;
            
            return [
                'success' => true,
                'message' => 'Profile updated successfully.'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Profile update failed. Please try again.'
            ];
        }
    }
    
    // Change password
    public function changePassword($currentPassword, $newPassword) {
        // Get current password
        $sql = "SELECT Password FROM USER WHERE User_ID = $this->userId";
        $user = fetch_row($sql);
        
        // Verify current password
        if (!password_verify($currentPassword, $user['Password'])) {
            return [
                'success' => false,
                'message' => 'Current password is incorrect.'
            ];
        }
        
        // Hash new password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        // Update password
        $sql = "UPDATE USER SET Password = '$hashedPassword' WHERE User_ID = $this->userId";
        
        if (execute_query($sql)) {
            return [
                'success' => true,
                'message' => 'Password changed successfully.'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Password change failed. Please try again.'
            ];
        }
    }
}