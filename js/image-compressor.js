/**
 * ConsuTrade - Image Compressor
 * Author: Kamogelo Phale
 * 
 * Converts images to WebP format on the client side.
 */

class ImageCompressor {
    constructor(options = {}) {
        this.maxWidth = options.maxWidth || 1200;
        this.maxHeight = options.maxHeight || 1200;
        this.quality = options.quality || 0.8;
        this.format = options.format || 'image/webp';
    }

    async compress(file, onProgress = null) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = (event) => {
                const img = new Image();
                img.onload = () => {
                    try {
                        const canvas = document.createElement('canvas');
                        let width = img.width;
                        let height = img.height;

                        if (width > this.maxWidth) {
                            height = Math.round((height * this.maxWidth) / width);
                            width = this.maxWidth;
                        }
                        if (height > this.maxHeight) {
                            width = Math.round((width * this.maxHeight) / height);
                            height = this.maxHeight;
                        }

                        canvas.width = width;
                        canvas.height = height;

                        const ctx = canvas.getContext('2d');
                        ctx.imageSmoothingEnabled = true;
                        ctx.imageSmoothingQuality = 'high';
                        ctx.drawImage(img, 0, 0, width, height);

                        canvas.toBlob((blob) => {
                            if (!blob) {
                                reject(new Error('Failed to create blob'));
                                return;
                            }
                            const baseName = file.name.replace(/\.[^/.]+$/, '');
                            const fileName = baseName + '.webp';
                            const compressedFile = new File([blob], fileName, { type: 'image/webp' });
                            if (onProgress) onProgress(100);
                            resolve(compressedFile);
                        }, 'image/webp', this.quality);
                    } catch (error) {
                        reject(error);
                    }
                };
                img.onerror = () => reject(new Error('Failed to load image'));
                img.src = event.target.result;
            };
            reader.onerror = () => reject(new Error('Failed to read file'));
            reader.readAsDataURL(file);
        });
    }

    async compressMultiple(files, onProgress = null) {
        const results = [];
        let completed = 0;

        for (let i = 0; i < files.length; i++) {
            try {
                const compressed = await this.compress(files[i], (percent) => {
                    if (onProgress) onProgress(i, percent);
                });
                results.push(compressed);
                completed++;
                if (onProgress) {
                    onProgress('total', Math.round((completed / files.length) * 100));
                }
            } catch (error) {
                console.error('Failed to compress image:', error);
                // FALLBACK: If compression fails, use the original file
                results.push(files[i]);
            }
        }
        return results;
    }
}
