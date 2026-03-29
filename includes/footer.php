<?php
// includes/footer.php
?>
        <footer class="footer">
            <div class="container">
                <p>&copy; <?php echo date('Y'); ?> YoursTruly Tours. Empowering Discoveries.</p>
            </div>
        </footer>

        <!-- Static Support Chat Representation (Vanilla JS inside main.js) -->
        <div class="support-chat" id="supportChatWidget">
            <button class="chat-toggle" id="chatToggleBtn">
                <i class="fa-regular fa-comment-dots"></i>
            </button>
            <div id="chatWindow" class="chat-window" style="display: none;">
                <div style="background: var(--primary); color: white; padding: 1rem; font-weight: bold; text-align: center;">
                    Support Chat
                </div>
                <!-- Chat Body -->
                <div id="chatBody" style="flex: 1; padding: 1rem; overflow-y: auto; color: var(--text-main); font-size: 0.9rem;">
                    <div id="chatPlaceholder" style="text-align: center; color: var(--text-muted); display:flex; flex-direction: column; justify-content: center; height: 100%;">
                        <i class="fa-solid fa-headset" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                        <p>Hi! Ask me: "Are you open today?" or about our "Pet policy".</p>
                    </div>
                </div>
                <!-- Chat Input -->
                <div style="padding: 1rem; border-top: 1px solid var(--border);">
                    <input type="text" id="chatInput" placeholder="Type a message and press Enter..." style="width: 100%;">
                </div>
            </div>
        </div>
        <?php if (isset($is_admin_layout) && $is_admin_layout): ?>
            </div> <!-- End .base-content -->
        <?php endif; ?>
    </div> <!-- End .App -->

    <script src="assets/js/main.js"></script>
</body>
</html>
