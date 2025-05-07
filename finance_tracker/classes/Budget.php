<?php
require_once __DIR__ . '/../config/database.php';

class Budget {
    private $budgetId;
    private $amount;
    private $month;
    private $categoryId;
    private $userId;
    
    // Constructor
    public function __construct($budgetId = null) {
        if ($budgetId) {
            $this->budgetId = $budgetId;
            $this->loadBudget();
        }
    }
    
    // Load budget data from database
    private function loadBudget() {
        $sql = "SELECT * FROM BUDGET WHERE Budget_ID = $this->budgetId";
        $result = fetch_row($sql);
        
        if ($result) {
            $this->amount = $result['Amount'];
            $this->month = $result['Month'];
            $this->categoryId = $result['Category_ID'];
            $this->userId = $result['User_ID'];
        }
    }
    
    // Getters
    public function getId() {
        return $this->budgetId;
    }
    
    public function getAmount() {
        return $this->amount;
    }
    
    public function getMonth() {
        return $this->month;
    }
    
    public function getCategoryId() {
        return $this->categoryId;
    }
    
    public function getUserId() {
        return $this->userId;
    }
    
    // Get all budgets by user ID
    public static function getByUserId($userId) {
        $sql = "SELECT b.*, c.Category_Name 
                FROM BUDGET b
                JOIN CATEGORY c ON b.Category_ID = c.Category_ID
                WHERE b.User_ID = $userId
                ORDER BY b.Month DESC, c.Category_Name";
        return fetch_all($sql);
    }
    
    // Get budgets by user ID and month
    public static function getByUserIdAndMonth($userId, $month) {
        $sql = "SELECT b.*, c.Category_Name 
                FROM BUDGET b
                JOIN CATEGORY c ON b.Category_ID = c.Category_ID
                WHERE b.User_ID = $userId
                AND b.Month = '$month'
                ORDER BY c.Category_Name";
        return fetch_all($sql);
    }
    
    // Get budget summary with spending for a month
    public static function getBudgetSummary($userId, $month) {
        $sql = "SELECT b.Budget_ID, b.Amount as budget_amount, c.Category_Name, c.Category_ID,
                    COALESCE((
                        SELECT SUM(t.Amount) 
                        FROM TRANSACTION t 
                        WHERE t.Category_ID = b.Category_ID 
                          AND t.User_ID = b.User_ID 
                          AND t.Type = 'expense'
                          AND DATE_FORMAT(t.Date, '%Y-%m') = b.Month
                    ), 0) as spent_amount
                FROM BUDGET b
                JOIN CATEGORY c ON b.Category_ID = c.Category_ID
                WHERE b.User_ID = $userId
                AND b.Month = '$month'
                ORDER BY c.Category_Name";
        return fetch_all($sql);
    }
    
    // Get budget by ID
    public static function getById($budgetId) {
        $sql = "SELECT b.*, c.Category_Name 
                FROM BUDGET b
                JOIN CATEGORY c ON b.Category_ID = c.Category_ID
                WHERE b.Budget_ID = " . (int)$budgetId;
        return fetch_row($sql);
    }
    
    // Check if budget exists for user, category and month
    public static function exists($userId, $categoryId, $month) {
        $sql = "SELECT COUNT(*) as count 
                FROM BUDGET 
                WHERE User_ID = $userId
                AND Category_ID = $categoryId
                AND Month = '$month'";
        $result = fetch_row($sql);
        return $result && $result['count'] > 0;
    }
    
    // Create a new budget
    public static function create($amount, $month, $categoryId, $userId) {
        // Check if budget already exists for this month and category
        if (self::exists($userId, $categoryId, $month)) {
            return [
                'success' => false,
                'message' => 'Budget already exists for this category and month.'
            ];
        }
        
        $sql = "INSERT INTO BUDGET (Amount, Month, Category_ID, User_ID) 
                VALUES ($amount, '$month', $categoryId, $userId)";
                
        if (execute_query($sql)) {
            return [
                'success' => true,
                'message' => 'Budget added successfully.',
                'budget_id' => last_insert_id()
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to add budget. Please try again.'
            ];
        }
    }
    
    // Update an existing budget
    public function update($amount) {
        $sql = "UPDATE BUDGET 
                SET Amount = $amount 
                WHERE Budget_ID = $this->budgetId";
                
        if (execute_query($sql)) {
            // Update object property
            $this->amount = $amount;
            
            return [
                'success' => true,
                'message' => 'Budget updated successfully.'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to update budget. Please try again.'
            ];
        }
    }
    
    // Delete a budget (static)
    public static function delete($budgetId) {
        $sql = "DELETE FROM BUDGET WHERE Budget_ID = " . (int)$budgetId;
        return execute_query($sql);
    }
}