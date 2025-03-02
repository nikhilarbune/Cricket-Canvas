<?php 
include 'header.php';

$status = isset($_GET['status']) ? $_GET['status'] : '';
?>

<div class="container my-5">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <h1 class="text-center mb-4">Contact Us</h1>
            
            <?php if($status === 'success'): ?>
            <div class="alert alert-success">
                Thank you for your message. We'll get back to you soon!
            </div>
            <?php endif; ?>

            <div class="contact-form-container bg-white rounded shadow p-4">
                <form action="process_contact.php" method="POST" class="contact-form">
                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>

                    <div class="mb-3">
                        <label for="subject" class="form-label">Subject</label>
                        <input type="text" class="form-control" id="subject" name="subject" required>
                    </div>

                    <div class="mb-3">
                        <label for="message" class="form-label">Message</label>
                        <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">Send Message</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
include 'footer.php';
?>