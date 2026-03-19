<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type");

// Database configuration
$host = 'localhost';
$dbname = 'your_database_name';
$username = 'your_username';
$password = 'your_password';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$request = explode('/', trim($_SERVER['PATH_INFO'] ?? '', '/'));
$id = $request[0] ?? null;

switch($method) {
    case 'GET':
        if ($id) {
            getBook($pdo, $id);
        } else {
            getBooks($pdo);
        }
        break;
        
    case 'POST':
        createBook($pdo);
        break;
        
    case 'PUT':
        if ($id) {
            updateBook($pdo, $id);
        } else {
            echo json_encode(['error' => 'Book ID required']);
        }
        break;
        
    case 'DELETE':
        if ($id) {
            deleteBook($pdo, $id);
        } else {
            echo json_encode(['error' => 'Book ID required']);
        }
        break;
        
    default:
        echo json_encode(['error' => 'Method not allowed']);
}

function getBooks($pdo) {
    try {
        $category = $_GET['category'] ?? '';
        $featured = $_GET['featured'] ?? '';
        $search = $_GET['search'] ?? '';
        
        $sql = "SELECT * FROM books WHERE 1=1";
        $params = [];
        
        if ($category) {
            $sql .= " AND category = ?";
            $params[] = $category;
        }
        
        if ($featured === 'true') {
            $sql .= " AND is_featured = 1";
        }
        
        if ($search) {
            $sql .= " AND (title LIKE ? OR author LIKE ? OR description LIKE ?)";
            $searchTerm = "%$search%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Increment view count for all books in the list? (Optional)
        // You might want to track this differently
        
        echo json_encode([
            'success' => true,
            'data' => $books,
            'count' => count($books)
        ]);
        
    } catch(PDOException $e) {
        echo json_encode(['error' => 'Failed to fetch books: ' . $e->getMessage()]);
    }
}

function getBook($pdo, $id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
        $stmt->execute([$id]);
        $book = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($book) {
            // Increment view count
            $pdo->prepare("UPDATE books SET view_count = view_count + 1 WHERE id = ?")->execute([$id]);
            
            echo json_encode([
                'success' => true,
                'data' => $book
            ]);
        } else {
            echo json_encode(['error' => 'Book not found']);
        }
    } catch(PDOException $e) {
        echo json_encode(['error' => 'Failed to fetch book: ' . $e->getMessage()]);
    }
}

function createBook($pdo) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['title']) || !isset($data['author'])) {
            echo json_encode(['error' => 'Title and author are required']);
            return;
        }
        
        $sql = "INSERT INTO books (title, author, description, cover_image, pdf_file, category, pages, published_year, is_featured) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $data['title'],
            $data['author'],
            $data['description'] ?? null,
            $data['cover_image'] ?? null,
            $data['pdf_file'] ?? null,
            $data['category'] ?? null,
            $data['pages'] ?? null,
            $data['published_year'] ?? null,
            $data['is_featured'] ?? false
        ]);
        
        $id = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'message' => 'Book created successfully',
            'id' => $id
        ]);
        
    } catch(PDOException $e) {
        echo json_encode(['error' => 'Failed to create book: ' . $e->getMessage()]);
    }
}

function updateBook($pdo, $id) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $fields = [];
        $params = [];
        
        $updatable = ['title', 'author', 'description', 'cover_image', 'pdf_file', 'category', 'pages', 'published_year', 'is_featured'];
        
        foreach ($updatable as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $params[] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            echo json_encode(['error' => 'No fields to update']);
            return;
        }
        
        $params[] = $id;
        $sql = "UPDATE books SET " . implode(', ', $fields) . " WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Book updated successfully']);
        } else {
            echo json_encode(['error' => 'Book not found or no changes made']);
        }
        
    } catch(PDOException $e) {
        echo json_encode(['error' => 'Failed to update book: ' . $e->getMessage()]);
    }
}

function deleteBook($pdo, $id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM books WHERE id = ?");
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Book deleted successfully']);
        } else {
            echo json_encode(['error' => 'Book not found']);
        }
        
    } catch(PDOException $e) {
        echo json_encode(['error' => 'Failed to delete book: ' . $e->getMessage()]);
    }
}
?>