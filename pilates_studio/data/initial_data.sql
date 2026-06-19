-- 插入課程類型
INSERT INTO course_types (type_name, description) VALUES 
('器械皮拉提斯', '使用專業器械進行全身塑形'),
('芭蕾塑身', '結合芭蕾元素的優雅塑身課程'),
('器械禪柔', '融合禪學理念的柔和課程'),
('墊上皮拉提斯', '無器械基礎塑形課程');

-- 插入產品分類
INSERT INTO product_categories (category_name, description) VALUES 
('瑜珈衣', '各式瑜珈運動上衣'),
('瑜珈褲', '各式瑜珈運動褲'),
('運動內衣', '高支撐運動內衣'),
('止滑襪', '普通止滑襪'),
('五指止滑襪', '五指設計止滑襪'),
('瑜珈磚', '瑜珈輔助磚'),
('彈力繩', '瑜珈訓練彈力繩'),
('瑜珈球', '多種尺寸瑜珈球'),
('滾筒', '瑜珈按摩滾筒'),
('啞鈴', '瑜珈訓練啞鈴'),
('瑜珈墊', '高品質瑜珈墊');

-- 插入測試用戶 - 管理員
INSERT INTO users (username, email, password, role, full_name, phone) VALUES 
('admin', 'admin@example.com', '$2y$12$...', 'admin', '管理員', '0912345678');

-- 插入測試用戶 - 教練
INSERT INTO users (username, email, password, role, full_name, phone) VALUES 
('trainer1', 'trainer1@example.com', '$2y$12$...', 'trainer', '王教練', '0912345679'),
('trainer2', 'trainer2@example.com', '$2y$12$...', 'trainer', '李教練', '0912345680'),
('trainer3', 'trainer3@example.com', '$2y$12$...', 'trainer', '陳教練', '0912345681');

-- 插入測試用戶 - 顧客
INSERT INTO users (username, email, password, role, full_name, phone) VALUES 
('customer1', 'customer1@example.com', '$2y$12$...', 'customer', '林會員', '0912345682'),
('customer2', 'customer2@example.com', '$2y$12$...', 'customer', '張會員', '0912345683');

-- 插入教練信息
INSERT INTO trainers (user_id, introduction, experience_years, specialization, approval_status) VALUES 
(2, '擁有5年的皮拉提斯教學經驗，專長於器械皮拉提斯。幫助學員通過科學的訓練達到塑身效果。', 5, '器械皮拉提斯', 'approved'),
(3, '具有8年的瑜珈和皮拉提斯教學經驗，曾獲得多項國際認證。專注於個性化教學。', 8, '芭蕾塑身、器械皮拉提斯', 'approved'),
(4, '擁有3年教學經驗，擅長墊上皮拉提斯和柔和課程。幫助初學者快速入門。', 3, '墊上皮拉提斯、器械禪柔', 'approved');

-- 插入課程
INSERT INTO courses (course_type_id, trainer_id, course_name, description, price, max_capacity, course_type) VALUES 
(1, 1, '器械皮拉提斯基礎班', '適合初學者的器械皮拉提斯入門課程', 1500, 1, 'one-on-one'),
(1, 1, '器械皮拉提斯雙人班', '兩人共享一位專業教練的指導', 1000, 2, 'one-on-two'),
(1, 1, '器械皮拉提斯小班課', '小班制課程，最多5人', 600, 5, 'group-class'),
(2, 2, '芭蕾塑身一對一', '個性化芭蕾塑身課程', 1500, 1, 'one-on-one'),
(2, 2, '芭蕾塑身小班課', '優雅塑身小班課程', 600, 5, 'group-class'),
(3, 3, '器械禪柔放鬆班', '身心靈放鬆課程', 1500, 1, 'one-on-one'),
(4, 3, '墊上皮拉提斯基礎', '無器械基礎課程', 600, 5, 'group-class');

-- 插入示例課程安排（需要根據實際日期調整）
-- 插入產品
INSERT INTO products (category_id, product_name, description, price, stock) VALUES 
(1, '黑色短袖瑜珈衣', '舒適透氣的黑色瑜珈短袖', 399, 50),
(1, '紫色長袖瑜珈衣', '保暖的紫色瑜珈長袖', 499, 40),
(2, '黑色九分瑜珈褲', '修身顯瘦的九分褲', 599, 60),
(2, '灰色五分瑜珈褲', '涼爽舒適的五分褲', 499, 45),
(3, '黑色高強度運動內衣', '強支撐運動內衣', 799, 30),
(3, '紫色中強度運動內衣', '中等支撐運動內衣', 599, 35),
(4, '黑色踝襪', '止滑踝襪3雙組', 299, 100),
(4, '灰色長襪', '止滑長襪單雙', 199, 80),
(5, '黑色五指踝襪', '五指止滑踝襪', 399, 50),
(6, '瑜珈磚組合', '環保瑜珈磚2片', 399, 70),
(7, '彈力繩套組', '多色彈力繩5條', 499, 60),
(8, '瑜珈球65cm', '高品質瑜珈球', 799, 40),
(9, '按摩滾筒', '深層按摩滾筒', 699, 45),
(10, '調整式啞鈴對', '1-5kg調整啞鈴', 999, 35),
(11, '防滑瑜珈墊', '環保防滑瑜珈墊6mm', 1299, 50);