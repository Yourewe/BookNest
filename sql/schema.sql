CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    user_type ENUM('customer', 'admin') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE books (
    book_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255) NOT NULL,
    genre VARCHAR(100),
    price DECIMAL(10, 2) NOT NULL,
    description TEXT,
    cover_image VARCHAR(255),
    stock_quantity INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    total_amount DECIMAL(10, 2) NOT NULL,
    status VARCHAR(50) DEFAULT 'Pending',
    shipping_address TEXT,
    phone VARCHAR(20),
    payment_method VARCHAR(50),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE order_details (
    detail_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    book_id INT NOT NULL,
    quantity INT NOT NULL,
    subtotal DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(book_id) ON DELETE CASCADE
);

CREATE TABLE wishlist (
    wishlist_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(book_id) ON DELETE CASCADE,
    UNIQUE KEY unique_wishlist (user_id, book_id)
);

-- Add indexes for better performance
CREATE INDEX idx_books_genre ON books(genre);
CREATE INDEX idx_books_author ON books(author);
CREATE INDEX idx_books_price ON books(price);
CREATE INDEX idx_orders_user ON orders(user_id);
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_orders_date ON orders(order_date);

-- Insert default admin user (password: admin123)
INSERT INTO users (name, email, password, user_type) VALUES 
('Admin User', 'resrpawal@gmail.com', '$2y$10$O9vmpQqVaNzTkksp8oKGr.k.qt.7F3tYTf8h0uMsadJ.ZJ4JBXIEC', 'admin');

-- Insert sample books
INSERT INTO books (title, author, genre, price, description, stock_quantity) VALUES
('The Great Gatsby', 'F. Scott Fitzgerald', 'Fiction', 299.99, 'A classic American novel set in the Jazz Age, exploring themes of wealth, love, and the American Dream.', 50),
('To Kill a Mockingbird', 'Harper Lee', 'Fiction', 349.99, 'A gripping tale of racial injustice and childhood innocence in the American South.', 45),
('1984', 'George Orwell', 'Dystopian Fiction', 399.99, 'A dystopian social science fiction novel about totalitarian control and surveillance.', 60),
('Pride and Prejudice', 'Jane Austen', 'Romance', 279.99, 'A romantic novel that critiques the British landed gentry at the end of the 18th century.', 40),
('The Catcher in the Rye', 'J.D. Salinger', 'Fiction', 329.99, 'A controversial novel about teenage rebellion and alienation in post-war America.', 35),
('Harry Potter and the Philosopher\'s Stone', 'J.K. Rowling', 'Fantasy', 450.99, 'The first book in the beloved Harry Potter series about a young wizard\'s adventures.', 75),
('The Lord of the Rings', 'J.R.R. Tolkien', 'Fantasy', 899.99, 'An epic high-fantasy novel about the quest to destroy the One Ring.', 30),
('Think and Grow Rich', 'Napoleon Hill', 'Self-Help', 250.99, 'A personal development and self-improvement book on achieving financial success.', 55);
