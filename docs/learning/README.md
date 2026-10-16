# Bộ nhớ dự án theo ECC

Nguồn chính: [BUG_LESSONS.md](../BUG_LESSONS.md). Điểm dẫn đọc tự động trong ngữ cảnh dự án: [AGENTS.md](../../AGENTS.md). Bản máy đọc: [instincts.json](instincts.json).

Đây là vận dụng thủ công mô hình trigger/action/evidence/confidence/project-scope của ECC continuous-learning-v2. Không cài Claude hooks, không có observer chạy nền, không gửi transcript ra ngoài, không sửa global skill và không tuyên bố mô hình được huấn luyện lại. Giới hạn nền tảng native Windows của observer đã được đọc trong nguồn skill. Chỉ tái sử dụng ở repo DeraSoft PM; pattern dự án khác cần được kiểm chứng trước khi promotion.

## Trước khi sửa lỗi

Đọc mục khớp triệu chứng, đối chiếu source hiện tại và quyết định mới nhất. Coi nguyên nhân cũ là giả thuyết cần tái hiện, không phải chẩn đoán mặc định. Chạy test đích được dẫn và ghi kết quả thật. Với những bài chưa có regression tự động, giữ rõ bằng chứng UAT/tình huống rủi ro.

## Thêm hoặc sửa bài học

1. Sửa `docs/BUG_LESSONS.md`, một ID ổn định cho mỗi tình huống; có anchor cùng ID viết thường.
2. Ghi đủ Loại, Khi gặp, Nguyên nhân, Hành động, Kiểm tra, Bằng chứng. Đánh dấu phần chưa xác minh; không lưu raw log, credential, token, địa chỉ liên hệ thật hoặc dump.
3. Chỉ sửa số liệu sau khi đọc log có thật; không copy PASS lịch sử thành lần chạy mới. Cập nhật ngày trong generator khi tạo mốc mới, giữ lịch sử bằng Git.
4. Chạy `python docs/learning/sync_memory.py --sync`, rồi `python docs/learning/sync_memory.py --check`.
5. Review diff, commit các file liên quan; không đưa vào gói runtime/production.

Generator chỉ đọc Markdown/AGENTS, kiểm tra các đường dẫn bằng chứng, rồi ghi đúng `docs/learning/instincts.json` khi có `--sync`. `--check` không ghi. Không truy cập network, Git config, database hoặc thư mục ngoài repo. Kiểm tra liên kết không chứng minh nội dung từng bằng chứng, nên vẫn cần review thủ công.

Trong lúc tạo bộ nhớ, parser đầu tiên bắt quá rộng do `.+` kết hợp DOTALL ở dòng tiêu đề và yêu cầu cứng dấu cách sau dấu hai chấm. Đã chuyển sang `[^\n]*`, split dấu hai chấm đầu tiên rồi strip và kiểm các trường bắt buộc; không bỏ mục lỗi để đủ số lượng. Bản kiểm tra đã xác nhận32 mục và69 đường dẫn bằng chứng/test tồn tại.

Confidence0.9 thể hiện mức tin cậy thủ công của quy tắc đã được rà nguồn;0.5 dành quy tắc chẩn đoán timeout còn mở. Không coi các số này là xác suất lỗi hoặc ECC tự quan sát. Bằng chứng phản bác có thể buộc sửa/giảm mức tin cậy; không giữ bài học cũ bất chấp dữ liệu mới.
