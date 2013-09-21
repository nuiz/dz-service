DZ API
==

การใช้งาน
ระบบ ยืนยันตัวตนใช้ระบ Token

post /auth โดยส่งค่า username(email) กับ password ไป จะได้ข้อมูล user และค่า token กลับมา

เมื่อใดก็ตามที่มีความผิดพลาดเกิดขึ้น ระบบจะส่ง json กลับมาเป็น { error: { message, code, type } }


เมื่อได้ token แล้ว เวลาจะ request ในฐานะ user ต้องใส่ header ชื่อ X-Auth-Token และใส่ token เข้าไป


ข้อมูล

user, news, showcase, activity, lesson, lesson/chapter , lesson/chapter/video, class, class/group, class/group/user

ตอนจะ get ข้อมูล

GET user ดึงข้อมูล user เป็นลิส
GET user/1 ดึงข้อมูล user id 1
PUT user/1 แก้ไข้ข้อมูล user id 1
DELETE user/1 ลบข้อมูล user id 1

ถ้าเป็นข้อมูลที่เป็น list เช่น user หลายๆคน จะได้รูปแบบข้อมูลเป็น { int length, array data } เสมอ
เช่น GET user จะได้ {length: 3, data: [user1, user2, user3]}

ข้อมูลย่อย เช่น chapter ใน lesson

GET lesson/1/chapter ดึงข้อมูล chapter ที่อยู่ใน lesson id 1
GET lesson/1/chapter/2/video ดึง video ทีอยู่ใน chapter id 2

/**************** comment และ like(ยังไม่ได้ทำ) *************/

user , news, lesson, chapter, showcase, video จะมี id ที่ไม่ซ้ำกัน

เมื่อต้องการ comment ใน object ไหน
POST dz_object/1/comment คือการ comment กับ object id 1
GET dz_object/1/comment คือการดึง comment ของ object id 1 ออกมาเป็นลิส

GET dz_object/1/like ดึงข้อมูลว่ามีคนไลค์ object id 1 ไปกี่คน like
POST dz_object/1/like กดไลค์
DELETE dz_object/1/like การ unlike


