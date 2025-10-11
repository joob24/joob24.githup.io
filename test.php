<?php
session_start();  // Mulai session
require_once 'config.php';  // Include koneksi DB

// Cek session: Jika nama siswa tidak ada, redirect ke index
if (!isset($_SESSION['student_name']) || empty($_SESSION['student_name']) || 
    !isset($_SESSION['class_id']) || !isset($_SESSION['subject_slug'])) {
    header('Location: index.html');
    exit;
}

$student_name = htmlspecialchars($_SESSION['student_name']);
$class_id = intval($_SESSION['class_id']);
$subject_slug = htmlspecialchars($_SESSION['subject_slug']);
$chapter_slug = isset($_GET['chapter']) ? htmlspecialchars($_GET['chapter']) : '';

$error = '';
$questions = [];
$chapter_info = [];
$current_question = 1;
$selected_answers = [];  // Array jawaban user [1 => 'A', 2 => 'B', ...]
$current_score = 0;
$total_questions = 20;
$is_completed = false;
$final_score = 0;

// PERBAIKAN: Fetch chapter_info dan questions SEBELUM POST handling, agar selalu tersedia untuk cek skor
if (!empty($chapter_slug)) {
    try {
        // Fetch chapter info
        $stmt = $pdo->prepare("
            SELECT c.id AS chapter_id, c.title, c.description, s.name AS subject_name, s.id AS subject_id 
            FROM chapters c 
            JOIN subjects s ON c.subject_id = s.id 
            WHERE c.slug = ?
        ");
        $stmt->execute([$chapter_slug]);
        $chapter_info = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$chapter_info) {
            $error = 'Bab tidak ditemukan!';
        }
    } catch (PDOException $e) {
        $error = 'Error fetch chapter: ' . $e->getMessage();
    }

    // Fetch 20 questions (jika chapter OK)
    if (empty($error) && !empty($chapter_info)) {
        try {
            $stmt = $pdo->prepare("
                SELECT * FROM questions 
                WHERE chapter_id = ? 
                ORDER BY order_num ASC 
                LIMIT 20
            ");
            $stmt->execute([$chapter_info['chapter_id']]);
            $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($questions) < $total_questions) {
                $error = 'Belum ada 20 pertanyaan lengkap untuk bab ini. Hubungi admin.';
            }
        } catch (PDOException $e) {
            $error = 'Error fetch questions: ' . $e->getMessage();
        }
    }
}

// Inisialisasi session untuk state kuis jika belum ada
if (!isset($_SESSION['test_state'])) {
    $_SESSION['test_state'] = [
        'current_question' => 1,
        'selected_answers' => [],
        'current_score' => 0,
        'chapter_slug' => $chapter_slug
    ];
}

// Handle POST: Jawaban user (sekarang $questions sudah tersedia)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    $user_answer = isset($_POST['answer']) ? strtoupper(trim($_POST['answer'])) : '';
    $current_question = intval($_POST['current_question']);

    if (empty($user_answer) || !in_array($user_answer, ['A', 'B', 'C', 'D'])) {
        $error = 'Pilih jawaban yang valid!';
    } else {
        // Simpan jawaban user
        $_SESSION['test_state']['selected_answers'][$current_question] = $user_answer;

        // Cek jawaban benar dan update skor (sekarang $questions tersedia)
        if (isset($questions[$current_question - 1])) {
            $correct = $questions[$current_question - 1]['correct_option'];
            if ($user_answer === $correct) {
                $_SESSION['test_state']['current_score'] += 5;
            }
        }

        // Lanjut ke soal berikutnya atau selesai
        if ($current_question < $total_questions) {
            $_SESSION['test_state']['current_question'] = $current_question + 1;
            header('Location: ' . $_SERVER['PHP_SELF'] . '?chapter=' . urlencode($chapter_slug));  // Reload untuk soal baru
            exit;
        } else {
            // Selesai: Hitung final score dan simpan ke DB
            $final_score = $_SESSION['test_state']['current_score'];
            $is_completed = true;

            // Insert ke test_results (chapter_id dan subject_id sudah dari fetch atas)
            try {
                $chapter_id = $chapter_info['chapter_id'];
                $subject_id = $chapter_info['subject_id'];

                $stmt = $pdo->prepare("
                    INSERT INTO test_results (student_name, class_id, subject_id, chapter_id, score) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$student_name, $class_id, $subject_id, $chapter_id, $final_score]);
            } catch (PDOException $e) {
                $error = 'Error simpan hasil: ' . $e->getMessage();
            }

            // Reset state setelah simpan
            unset($_SESSION['test_state']);
        }
    }
} else {
    // Inisialisasi atau reset state jika GET
    if ($_SESSION['test_state']['chapter_slug'] !== $chapter_slug) {
        $_SESSION['test_state'] = [
            'current_question' => 1,
            'selected_answers' => [],
            'current_score' => 0,
            'chapter_slug' => $chapter_slug
        ];
    }
    $current_question = $_SESSION['test_state']['current_question'];
    $selected_answers = $_SESSION['test_state']['selected_answers'];
    $current_score = $_SESSION['test_state']['current_score'];
}

// Jika error atau selesai, handle tampilan
if (!empty($error) && !$is_completed) {
    // Tampilkan error dan redirect option
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Kuis - EduPlay</title>
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom Theme CSS -->
    <style>
        :root {
            --primary-color: #3b82f6; --secondary-color: #10b981; --accent-color: #f59e0b;
            --bg-gradient: linear-gradient(135deg, #e0f2fe 0%, #dcfce7 100%);
            --correct-bg: #d4edda; --wrong-bg: #f8d7da;
        }
        body { font-family: 'Comic Sans MS', cursive, sans-serif; background: var(--bg-gradient); color: #1f2937; padding-top: 100px; }
        .hero { background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white; padding: 60px 0; text-align: center; margin-bottom: 2rem; }
        .hero-content { position: relative; z-index: 5; }
        .text-shadow { text-shadow: 2px 2px 4px rgba(0,0,0,0.7); }
        .quiz-container { max-width: 800px; margin: 0 auto 2rem; background: rgba(255,255,255,0.95); border-radius: 15px; padding: 2rem; box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .progress-bar { height: 10px; background: #e9ecef; border-radius: 5px; overflow: hidden; margin-bottom: 1rem; }
        .progress-fill { height: 100%; background: var(--secondary-color); transition: width 0.3s ease; }
        .question-card { background: white; border: 2px solid var(--primary-color); border-radius: 10px; padding: 1.5rem; margin-bottom: 1rem; text-align: center; }
        .question-text { font-size: 1.2rem; font-weight: bold; margin-bottom: 1rem; color: var(--primary-color); }
        .option { display: block; margin: 0.5rem 0; padding: 0.75rem; border: 1px solid #ddd; border-radius: 8px; cursor: pointer; transition: all 0.3s; text-align: left; }
        .option:hover { background: rgba(59, 130, 246, 0.1); }
        .option input[type="radio"] { margin-right: 0.5rem; transform: scale(1.2); }
        .feedback { padding: 1rem; border-radius: 8px; margin: 1rem 0; font-weight: bold; animation: fadeIn 0.5s ease; }
        .feedback.correct { background: var(--correct-bg); color: #155724; border: 1px solid #c3e6cb; }
        .feedback.wrong { background: var(--wrong-bg); color: #721c24; border: 1px solid #f5c6cb; }
        .btn-lg { padding: 0.75rem 2rem; font-size: 1.1rem; }
        .btn:disabled { opacity: 0.6; cursor: not-allowed; }
        .results-hero { background: linear-gradient(135deg, var(--secondary-color), var(--accent-color)); color: white; padding: 80px 0; text-align: center; }
        .score-display { font-size: 4rem; font-weight: bold; color: var(--accent-color); text-shadow: 2px 2px 4px rgba(0,0,0,0.3); }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        @media (max-width: 768px) { .quiz-container { margin: 1rem; padding: 1rem; } .question-text { font-size: 1rem; } .option { text-align: center; } }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top">
        <div class="container">
            <a class="navbar-brand fw-bold fs-3" href="index.html">🧠 EduPlay</a>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="history.php"><i class="fas fa-chart-bar me-1"></i> History Nilai</a></li>
            </ul>
        </div>
    </nav>

    <?php if (!empty($error)): ?>
        <!-- Error Page -->
        <section class="hero">
            <div class="container hero-content">
                <h1 class="display-5 fw-bold mb-3 text-shadow">Oops! Ada Masalah</h1>
                <div class="alert alert-warning"><?php echo $error; ?></div>
                <a href="index.html" class="btn btn-light btn-lg">Kembali ke Beranda</a>
            </div>
        </section>
    <?php elseif ($is_completed): ?>
        <!-- Hasil Akhir -->
        <section class="results-hero">
            <div class="container hero-content">
                <h1 class="display-4 fw-bold mb-4 text-shadow">Selamat, <?php echo $student_name; ?>!</h1>
                <p class="lead mb-4 text-shadow">Kamu telah menyelesaikan test untuk <strong><?php echo htmlspecialchars($chapter_info['subject_name']); ?></strong> - <strong><?php echo htmlspecialchars($chapter_info['title']); ?></strong></p>
                <div class="score-display mb-4">Skor: <?php echo $final_score; ?>/100</div>
                <p class="fs-4 mb-4">Keren! Kamu menjawab <?php echo round($final_score / 5); ?> dari 20 soal dengan benar. Terus belajar ya! 🎉</p>
                <a href="history.php" class="btn btn-light btn-lg me-3">Lihat History Nilai</a>
                <a href="index.html" class="btn btn-outline-light btn-lg">Test Lagi</a>
            </div>
        </section>
    <?php else: ?>
        <!-- Kuis Ongoing -->
        <section class="hero">
            <div class="container hero-content">
                <h2 class="display-6 fw-bold mb-3 text-shadow">Halo, <?php echo $student_name; ?>!</h2>
                <p class="lead mb-4 text-shadow">Test untuk <strong><?php echo htmlspecialchars($chapter_info['subject_name']); ?></strong> - <strong><?php echo htmlspecialchars($chapter_info['title']); ?></strong></p>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php echo (($current_question - 1) / $total_questions * 100); ?>%;"></div>
                </div>
                <small class="text-light">Soal <?php echo $current_question; ?>/<?php echo $total_questions; ?> | Skor sementara: <?php echo $current_score; ?>/<?php echo $total_questions * 5; ?></small>
            </div>
        </section>

        <div class="container">
            <div class="quiz-container">
                <?php if ($current_question > 1): ?>
                    <!-- Feedback untuk soal sebelumnya -->
                    <?php 
                    $prev_q = $current_question - 1;
                    $prev_answer = isset($selected_answers[$prev_q]) ? $selected_answers[$prev_q] : '';
                    if (!empty($prev_answer) && isset($questions[$prev_q - 1])) {
                        $q = $questions[$prev_q - 1];
                        $correct = $q['correct_option'];
                        $is_correct = ($prev_answer === $correct);
                        $class = $is_correct ? 'correct' : 'wrong';
                        $msg = $is_correct ? 'Benar! 🎉' : 'Salah! Jawaban benar: ' . $correct;
                    }
                    ?>
                    <div class="feedback <?php echo $class ?? ''; ?>">
                        <i class="fas <?php echo $is_correct ? 'fa-check-circle' : 'fa-times-circle'; ?> me-2"></i>
                        <?php echo $msg ?? ''; ?><br>
                        <small><?php echo htmlspecialchars($q['explanation'] ?? 'Bagus usahanya!'); ?></small>
                    </div>
                <?php endif; ?>

                <!-- Soal Saat Ini -->
                <?php if (isset($questions[$current_question - 1])): ?>
                    <?php $q = $questions[$current_question - 1]; ?>
                    <form method="POST" id="quizForm">
                        <input type="hidden" name="current_question" value="<?php echo $current_question; ?>">
                        <div class="question-card">
                            <div class="question-text">
                                <strong>Soal <?php echo $current_question; ?>: </strong><?php echo htmlspecialchars($q['question_text']); ?>
                            </div>
                            <label class="option">
                                <input type="radio" name="answer" value="A"> A. <?php echo htmlspecialchars($q['option_a']); ?>
                            </label>
                            <label class="option">
                                <input type="radio" name="answer" value="B"> B. <?php echo htmlspecialchars($q['option_b']); ?>
                            </label>
                            <label class="option">
                                <input type="radio" name="answer" value="C"> C. <?php echo htmlspecialchars($q['option_c']); ?>
                            </label>
                            <label class="option">
                                <input type="radio" name="answer" value="D"> D. <?php echo htmlspecialchars($q['option_d']); ?>
                            </label>
                            <button type="submit" class="btn btn-primary btn-lg mt-3" id="nextBtn" disabled>
                                <?php echo ($current_question == $total_questions) ? 'Selesaikan Test' : 'Lanjut ke Soal Berikutnya'; ?> 🚀
                           
                           
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="alert alert-warning">Soal tidak ditemukan. <a href="index.html">Kembali ke Beranda</a></div>
                <?php endif; ?>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>  <!-- Tutup else kuis ongoing -->

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS untuk Interaktivitas Kuis (enable tombol saat pilih jawaban, animasi fade-in) -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('quizForm');
            const nextBtn = document.getElementById('nextBtn');
            const radios = form ? form.querySelectorAll('input[type="radio"]') : [];
            const feedbackElements = document.querySelectorAll('.feedback');
            const questionCard = document.querySelector('.question-card');

            // Enable/disable tombol berdasarkan pilihan radio
            if (radios.length > 0) {
                radios.forEach(radio => {
                    radio.addEventListener('change', function() {
                        nextBtn.disabled = false;
                        nextBtn.textContent = nextBtn.textContent.replace('🚀', '');  // Hapus emoji saat enabled
                        nextBtn.classList.add('btn-success');  // Ubah warna tombol saat enabled
                    });
                });
            }

            // Animasi fade-in untuk feedback dan soal baru (saat halaman load)
            if (feedbackElements.length > 0) {
                feedbackElements.forEach(el => {
                    el.style.opacity = '0';
                    el.style.transform = 'translateY(10px)';
                    setTimeout(() => {
                        el.style.transition = 'all 0.5s ease';
                        el.style.opacity = '1';
                        el.style.transform = 'translateY(0)';
                    }, 100);
                });
            }

            if (questionCard) {
                questionCard.style.opacity = '0';
                questionCard.style.transform = 'translateY(10px)';
                setTimeout(() => {
                    questionCard.style.transition = 'all 0.5s ease';
                    questionCard.style.opacity = '1';
                    questionCard.style.transform = 'translateY(0)';
                }, 300);
            }

            // Validasi submit (opsional: alert jika belum pilih)
            if (form) {
                form.addEventListener('submit', function(e) {
                    const selected = form.querySelector('input[type="radio"]:checked');
                    if (!selected) {
                        e.preventDefault();
                        alert('Pilih jawaban dulu sebelum lanjut!');
                        return false;
                    }
                });
            }

            // Debug console (hapus nanti jika produksi)
            console.log('Kuis loaded: Soal ' + <?php echo $current_question; ?> + ', Skor ' + <?php echo $current_score; ?>);
        });
    </script>
</body>
</html>