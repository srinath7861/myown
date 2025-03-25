<!DOCTYPE html>
<html>
<head>
    <title>Nexus Image Optimizer</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;500;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <style>
        :root {
            --primary: #FF6B35;
            --background: #0A0A0A;
            --surface: #1A1A1A;
            --text: #FFFFFF;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--background);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 2rem;
        }

        .container {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            width: 100%;
            max-width: 600px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        h1 {
            text-align: center;
            margin-bottom: 2rem;
            font-weight: 700;
            background: linear-gradient(45deg, #FF6B35, #FF8E53);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .input-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        input[type="file"] {
            display: none;
        }

        .custom-file-input {
            background: var(--surface);
            border: 2px dashed rgba(255, 255, 255, 0.1);
            padding: 1.5rem;
            border-radius: 12px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .custom-file-input:hover {
            border-color: var(--primary);
            background: rgba(255, 107, 53, 0.1);
        }

        input[type="number"] {
            width: 100%;
            padding: 0.8rem;
            background: var(--surface);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            color: var(--text);
            font-size: 1rem;
        }

        button {
            background: linear-gradient(45deg, #FF6B35, #FF8E53);
            border: none;
            padding: 1rem 2rem;
            color: white;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: transform 0.3s ease;
            width: 100%;
        }

        button:disabled {
            background: #333;
            cursor: not-allowed;
            opacity: 0.7;
        }

        button:hover:not(:disabled) {
            transform: translateY(-2px);
        }

        .status {
            margin-top: 1.5rem;
            text-align: center;
            min-height: 24px;
        }

        .loader {
            display: none;
            width: 50px;
            height: 50px;
            margin: 1rem auto;
            position: relative;
        }

        .loader::after {
            content: "";
            position: absolute;
            width: 100%;
            height: 100%;
            border: 4px solid transparent;
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .progress-bar {
            width: 100%;
            height: 4px;
            background: var(--surface);
            border-radius: 2px;
            margin: 1rem 0;
            overflow: hidden;
            display: none;
        }

        .progress {
            width: 0%;
            height: 100%;
            background: linear-gradient(90deg, #FF6B35, #FF8E53);
            transition: width 0.3s ease;
        }

        .glow {
            animation: glow 2s infinite alternate;
        }

        @keyframes glow {
            from {
                filter: drop-shadow(0 0 5px rgba(255, 107, 53, 0.5));
            }
            to {
                filter: drop-shadow(0 0 20px rgba(255, 107, 53, 0.8));
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="glow">⚡ NEXUS OPTIMIZER</h1>
        
        <div class="input-group">
            <label for="targetSize">Target Size (KB)</label>
            <input type="number" id="targetSize" value="100" min="10" max="500">
        </div>

        <input type="file" id="imageInput" accept="image/*" multiple>
        <label for="imageInput" class="custom-file-input">
            📁 Select Images (Multiple Supported)
        </label>

        <div class="progress-bar">
            <div class="progress"></div>
        </div>

        <div class="loader"></div>
        <div class="status"></div>

        <button id="downloadBtn" disabled>Download Optimized Files</button>
    </div>

    <script>
        const imageInput = document.getElementById('imageInput');
        const downloadBtn = document.getElementById('downloadBtn');
        const statusDiv = document.querySelector('.status');
        const targetSizeInput = document.getElementById('targetSize');
        const loader = document.querySelector('.loader');
        const progressBar = document.querySelector('.progress');
        let compressedFiles = [];

        async function compressImage(file, targetKB) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onload = async function(e) {
                    const img = new Image();
                    img.onload = async function() {
                        const canvas = document.createElement('canvas');
                        const ctx = canvas.getContext('2d');
                        
                        let width = img.width;
                        let height = img.height;
                        canvas.width = width;
                        canvas.height = height;
                        ctx.drawImage(img, 0, 0, width, height);

                        let quality = 0.9;
                        let attempts = 0;

                        const updateProgress = () => {
                            const progress = Math.min(attempts * 10, 100);
                            progressBar.style.width = `${progress}%`;
                        };

                        function attemptCompression() {
                            attempts++;
                            updateProgress();
                            
                            canvas.toBlob((blob) => {
                                if (!blob) return reject(new Error('Compression failed'));

                                if (blob.size <= targetKB * 1024) {
                                    const filename = file.name.replace(/\.[^.]+$/, '') + '.jpg';
                                    resolve({ blob, filename });
                                } else {
                                    quality -= 0.1;
                                    if (quality >= 0.1) {
                                        canvas.toBlob(attemptCompression, 'image/jpeg', quality);
                                    } else {
                                        width *= 0.9;
                                        height *= 0.9;
                                        canvas.width = width;
                                        canvas.height = height;
                                        ctx.drawImage(img, 0, 0, width, height);
                                        quality = 0.9;
                                        attemptCompression();
                                    }
                                }
                            }, 'image/jpeg', quality);
                        }

                        attemptCompression();
                    };
                    img.onerror = reject;
                    img.src = e.target.result;
                };
                reader.onerror = reject;
                reader.readAsDataURL(file);
            });
        }

        imageInput.addEventListener('change', async (e) => {
            const files = e.target.files;
            if (!files.length) return;

            document.querySelector('.progress-bar').style.display = 'block';
            loader.style.display = 'block';
            statusDiv.textContent = 'Optimizing your images...';
            downloadBtn.disabled = true;
            compressedFiles = [];

            try {
                const targetKB = parseInt(targetSizeInput.value) || 100;
                const promises = Array.from(files).map(file => compressImage(file, targetKB));
                compressedFiles = await Promise.all(promises);
                
                statusDiv.textContent = `Optimized ${compressedFiles.length} images. Ready for download!`;
                downloadBtn.disabled = false;
            } catch (error) {
                statusDiv.textContent = 'Error: ' + error.message;
            } finally {
                loader.style.display = 'none';
                document.querySelector('.progress-bar').style.display = 'none';
                progressBar.style.width = '0%';
            }
        });

        downloadBtn.addEventListener('click', async () => {
            if (!compressedFiles.length) return;

            try {
                if (compressedFiles.length === 1) {
                    const { blob, filename } = compressedFiles[0];
                    const link = document.createElement('a');
                    link.href = URL.createObjectURL(blob);
                    link.download = filename;
                    link.click();
                } else {
                    statusDiv.textContent = 'Packaging files...';
                    const zip = new JSZip();
                    compressedFiles.forEach(({ blob, filename }) => {
                        zip.file(filename, blob);
                    });

                    const content = await zip.generateAsync({ type: 'blob' });
                    const link = document.createElement('a');
                    link.href = URL.createObjectURL(content);
                    link.download = 'optimized_images.zip';
                    link.click();
                }
                statusDiv.textContent = 'Download started!';
            } catch (error) {
                statusDiv.textContent = 'Download failed: ' + error.message;
            }
        });
    </script>
</body>
</html>