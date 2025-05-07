<?php
require_once __DIR__ . '/../config/database.php';

class Goal {
    private $goalId;
    private $targetAmount;
    private $targetDate;
    private $progress;
    private $status;
    private $userId;
    
    // Constructor
    public function __construct($goalId = null) {
        if ($goalId) {
            $this->goalId = $goalId;
            $this->loadGoal();
        }
    }
    
    // Load goal data from database
    private function loadGoal() {
        $sql = "SELECT * FROM GOAL WHERE Goal_ID = $this->goalId";
        $result = fetch_row($sql);
        
        if ($result) {
            $this->targetAmount = $result['Target_Amount'];
            $this->targetDate = $result['Target_Date'];
            $this->progress = $result['Progress'];
            $this->status = $result['Status'];
            $this->userId = $result['User_ID'];
        }
    }
    
    // Getters
    public function getId() {
        return $this->goalId;
    }
    
    public function getTargetAmount() {
        return $this->targetAmount;
    }
    
    public function getTargetDate() {
        return $this->targetDate;
    }
    
    public function getProgress() {
        return $this->progress;
    }
    
    public function getStatus() {
        return $this->status;
    }
    
    public function getUserId() {
        return $this->userId;
    }
    
    // Get percentage of progress
    public function getPercentage() {
        if ($this->targetAmount <= 0) return 0;
        return min(100, ($this->progress / $this->targetAmount) * 100);
    }
    
    // Get days remaining
    public function getDaysRemaining() {
        $today = new DateTime();
        $targetDate = new DateTime($this->targetDate);
        $diff = $today->diff($targetDate);
        
        return $diff->invert ? 0 : $diff->days;
    }
    
    // Get all goals by user ID
    public static function getByUserId($userId) {
        $sql = "SELECT * FROM GOAL WHERE User_ID = $userId ORDER BY Target_Date";
        return fetch_all($sql);
    }
    
    // Get active goals (pending or in_progress)
    public static function getActiveByUserId($userId) {
        $sql = "SELECT * FROM GOAL 
                WHERE User_ID = $userId 
                AND Status != 'completed' 
                ORDER BY Target_Date";
        return fetch_all($sql);
    }
    
    // Get goal by ID
    public static function getById($goalId) {
        $sql = "SELECT * FROM GOAL WHERE Goal_ID = $goalId";
        return fetch_row($sql);
    }
    
    // Create a new goal
    public static function create($targetAmount, $targetDate, $userId) {
        $sql = "INSERT INTO GOAL (Target_Amount, Target_Date, Progress, Status, User_ID) 
                VALUES ($targetAmount, '$targetDate', 0, 'pending', $userId)";
                
        if (execute_query($sql)) {
            return [
                'success' => true,
                'message' => 'Goal added successfully.',
                'goal_id' => last_insert_id()
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to add goal. Please try again.'
            ];
        }
    }
    
    // Update goal progress
    public function updateProgress($progress) {
        // Determine status based on progress
        $status = 'pending';
        if ($progress > 0 && $progress < $this->targetAmount) {
            $status = 'in_progress';
        } else if ($progress >= $this->targetAmount) {
            $status = 'completed';
            $progress = $this->targetAmount; // Cap progress at target amount
        }
        
        $sql = "UPDATE GOAL 
                SET Progress = $progress, Status = '$status' 
                WHERE Goal_ID = $this->goalId";
                
        if (execute_query($sql)) {
            // Update object properties
            $this->progress = $progress;
            $this->status = $status;
            
            return [
                'success' => true,
                'message' => 'Goal progress updated successfully.'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to update goal progress. Please try again.'
            ];
        }
    }
    
    // Update goal details
    public function update($targetAmount, $targetDate) {
        $sql = "UPDATE GOAL 
                SET Target_Amount = $targetAmount, Target_Date = '$targetDate' 
                WHERE Goal_ID = $this->goalId";
                
        if (execute_query($sql)) {
            // Update object properties
            $this->targetAmount = $targetAmount;
            $this->targetDate = $targetDate;
            
            // Re-evaluate status based on new target
            $this->updateProgress($this->progress);
            
            return [
                'success' => true,
                'message' => 'Goal updated successfully.'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to update goal. Please try again.'
            ];
        }
    }
    
    // Delete a goal
    public function delete() {
        $sql = "DELETE FROM GOAL WHERE Goal_ID = $this->goalId";
                
        if (execute_query($sql)) {
            return [
                'success' => true,
                'message' => 'Goal deleted successfully.'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to delete goal. Please try again.'
            ];
        }
    }
}