<?php
require_once __DIR__ . '/../config/database.php';

class Category {
    private $categoryId;
    private $categoryName;
    private $userId;
    
    // Constructor
    public function __construct($categoryId = null) {
        if ($categoryId) {
            $this->categoryId = $categoryId;
            $this->loadCategory();
        }
    }
    
    // Load category data from database
    private function loadCategory() {
        $sql = "SELECT * FROM CATEGORY WHERE Category_ID = $this->categoryId";
        $result = fetch_row($sql);
        
        if ($result) {
            $this->categoryName = $result['Category_Name'];
            $this->userId = $result['User_ID'];
        }
    }
    
    // Getters
    public function getId() {
        return $this->categoryId;
    }
    
    public function getName() {
        return $this->categoryName;
    }
    
    public function getUserId() {
        return $this->userId;
    }
    
    // Get all categories by user ID
    public static function getByUserId($userId) {
        $sql = "SELECT * FROM CATEGORY WHERE User_ID = $userId ORDER BY Category_Name";
        return fetch_all($sql);
    }
    
    // Get categories by type (based on transactions)
    public static function getByUserIdAndType($userId, $type) {
        $sql = "SELECT DISTINCT c.* 
                FROM CATEGORY c
                JOIN TRANSACTION t ON c.Category_ID = t.Category_ID
                WHERE c.User_ID = $userId
                AND t.Type = '$type'
                ORDER BY c.Category_Name";
        return fetch_all($sql);
    }
    
    // Get category by ID
    public static function getById($categoryId) {
        $sql = "SELECT * FROM CATEGORY WHERE Category_ID = $categoryId";
        return fetch_row($sql);
    }
    
    // Create a new category
    public static function create($categoryName, $userId) {
        // Check if category already exists for this user
        $sql = "SELECT * FROM CATEGORY 
                WHERE Category_Name = '$categoryName' 
                AND User_ID = $userId";
        $result = execute_query($sql);
        
        if ($result->num_rows > 0) {
            return [
                'success' => false,
                'message' => 'Category already exists.'
            ];
        }
        
        $sql = "INSERT INTO CATEGORY (Category_Name, User_ID) 
                VALUES ('$categoryName', $userId)";
                
        if (execute_query($sql)) {
            return [
                'success' => true,
                'message' => 'Category added successfully.',
                'category_id' => last_insert_id()
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to add category. Please try again.'
            ];
        }
    }
    
    // Update a category
    public function update($categoryName) {
        // Check if category already exists for this user
        $sql = "SELECT * FROM CATEGORY 
                WHERE Category_Name = '$categoryName' 
                AND User_ID = $this->userId
                AND Category_ID != $this->categoryId";
        $result = execute_query($sql);
        
        if ($result->num_rows > 0) {
            return [
                'success' => false,
                'message' => 'Category already exists.'
            ];
        }
        
        $sql = "UPDATE CATEGORY 
                SET Category_Name = '$categoryName' 
                WHERE Category_ID = $this->categoryId";
                
        if (execute_query($sql)) {
            // Update object property
            $this->categoryName = $categoryName;
            
            return [
                'success' => true,
                'message' => 'Category updated successfully.'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to update category. Please try again.'
            ];
        }
    }
    
    // Delete a category
    public function delete() {
        // Check if category is used in transactions
        $sql = "SELECT COUNT(*) as count FROM TRANSACTION 
                WHERE Category_ID = $this->categoryId";
        $result = fetch_row($sql);
        
        if ($result && $result['count'] > 0) {
            return [
                'success' => false,
                'message' => 'Cannot delete category because it is used in transactions.'
            ];
        }
        
        // Check if category is used in budgets
        $sql = "SELECT COUNT(*) as count FROM BUDGET 
                WHERE Category_ID = $this->categoryId";
        $result = fetch_row($sql);
        
        if ($result && $result['count'] > 0) {
            return [
                'success' => false,
                'message' => 'Cannot delete category because it is used in budgets.'
            ];
        }
        
        $sql = "DELETE FROM CATEGORY WHERE Category_ID = $this->categoryId";
                
        if (execute_query($sql)) {
            return [
                'success' => true,
                'message' => 'Category deleted successfully.'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to delete category. Please try again.'
            ];
        }
    }
}