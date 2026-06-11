---
name: normalize-vietnamese-text
description: Chuan hoa van ban tieng Viet thanh ban viet dung chinh ta va dau cau. Use when Codex needs to fix Vietnamese text that is missing diacritics, has broken or non-Vietnamese-looking words, inconsistent capitalization, spacing, or punctuation, and convert it into natural, standard Vietnamese while preserving the original meaning.
---

# Normalize Vietnamese Text

Chuẩn hóa văn bản tiếng Việt thành câu chữ tự nhiên, đúng chính tả, đúng dấu và dễ đọc.

## Workflow

1. Đọc toàn bộ ngữ cảnh trước khi sửa từng từ.
2. Khôi phục dấu tiếng Việt cho các từ đang bị mất dấu nếu ngữ cảnh đủ rõ.
3. Sửa các từ hỏng, từ lẫn ký tự lạ, hoặc các cụm không phải tiếng Việt thành từ/cụm tiếng Việt gần nghĩa nhất theo ngữ cảnh.
4. Chuẩn hóa viết hoa, khoảng trắng, xuống dòng và dấu câu.
5. Giữ nguyên ý nghĩa, giọng điệu và mức độ trang trọng của bản gốc trừ khi người dùng yêu cầu viết lại.

## Output Rules

- Mặc định trả về bản văn đã sửa, không kèm phân tích dài dòng.
- Nếu người dùng yêu cầu, đưa thêm danh sách các chỗ đã sửa hoặc các chỗ còn mơ hồ.
- Nếu có nhiều cách hiểu hợp lý, chọn phương án tự nhiên nhất và nêu ngắn gọn giả định.
- Nếu một cụm quá mơ hồ để đoán chắc, giữ cấu trúc an toàn nhất thay vì bịa thêm ý mới.

## Preservation Rules

- Giữ nguyên tên người, tên công ty, thuật ngữ thương hiệu, URL, email, số điện thoại, mã nguồn, lệnh terminal và dữ liệu định danh khi không nên Việt hóa.
- Không tự dịch từ tiếng Anh chuyên ngành nếu chúng đang được dùng đúng mục đích trong ngữ cảnh.
- Không thay đổi số liệu, ngày tháng hay thông tin thực tế nếu chỉ đang làm nhiệm vụ chuẩn hóa câu chữ.

## Correction Priorities

- Ưu tiên sửa lỗi thiếu dấu: `khong` -> `không`, `cam on` -> `cảm ơn`.
- Ưu tiên sửa lỗi dấu câu và tách từ: `xin chao ban toi ten la an` -> `Xin chào bạn, tôi tên là An.`
- Ưu tiên sửa từ lỗi mã hóa hoặc từ méo nghĩa theo ngữ cảnh.
- Ưu tiên diễn đạt tiếng Việt tự nhiên thay vì bám sát từng ký tự sai của bản gốc.

## Ambiguity Handling

- Với câu ngắn nhưng rõ nghĩa, sửa dứt khoát.
- Với câu dài hoặc nhiều chỗ sai chồng lên nhau, sửa theo mạch nghĩa chung của cả đoạn.
- Với từ có thể là tên riêng, kiểm tra ngữ cảnh trước khi chuyển thành từ thuần Việt.

## Quick Examples

- `hom nay troi dep qua` -> `Hôm nay trời đẹp quá.`
- `e muon dat lich hop vao thu 5 dc ko` -> `Em muốn đặt lịch họp vào thứ 5 được không?`
- `toi da gui file cho khach hang nhung cau van bi thieu dau cau` -> `Tôi đã gửi file cho khách hàng, nhưng câu văn bị thiếu dấu câu.`

Đọc thêm ví dụ trong [references/examples.md](references/examples.md) khi cần xử lý trường hợp khó hoặc mơ hồ.
