<form action="{{ route('districts.import.csv') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <input type="file" name="csv_file" accept=".csv">
    <button type="submit">Import CSV</button>
</form>
