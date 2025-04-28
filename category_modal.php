<style>.modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        h2{
            color: #cfd9ff;
        }
        .modal-content {
            background: white;
            padding: 20px;
            border-radius: 10px;
            width: 50%;
            max-width: 600px;
            text-align: center;
            background-color: #0c1a36;
        }
        .category-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-top: 20px;
        }
        .category-grid div {
            padding: 10px;
            background: #e3f2fd;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .category-grid div:hover {
            background: #bbdefb;
        }
        .close-btn {
            background: red;
            color: white;
            padding: 5px 10px;
            border: none;
            cursor: pointer;
            border-radius: 5px;
            margin-top: 10px;
        }
        </style>
<div class="modal" id="categoryModal">
    <div class="modal-content">
        <h2>Select a Category</h2>
        <div class="category-grid">
            <div>Business & Finance</div>
            <div>Education & Academia</div>
            <div>Science & Research</div>
            <div>Agriculture & Environment</div>
            <div>Technology & IT</div>
            <div>Government & Public Data</div>
            <div>Geography & Mapping</div>
            <div>Commerce & Consumer Data</div>
            <div>Social & Media</div>
            <div>Health & Medicine</div>
        </div>
        <button class="close-btn" onclick="hideModal()">Close</button>
    </div>
</div>
