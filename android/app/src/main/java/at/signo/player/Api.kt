package at.signo.player

import org.json.JSONObject
import java.io.BufferedReader
import java.io.OutputStream
import java.net.HttpURLConnection
import java.net.URL

object Api {
    private const val TIMEOUT = 15000

    private fun open(url: String, method: String): HttpURLConnection {
        val c = URL(url).openConnection() as HttpURLConnection
        c.requestMethod = method
        c.connectTimeout = TIMEOUT
        c.readTimeout = TIMEOUT
        c.setRequestProperty("Accept", "application/json")
        c.setRequestProperty("User-Agent", "SignoPlayer/1.0 (Android)")
        return c
    }

    fun get(url: String): String? = try {
        val c = open(url, "GET")
        if (c.responseCode in 200..299) {
            c.inputStream.bufferedReader().use(BufferedReader::readText)
        } else null
    } catch (e: Exception) { null }

    fun postJson(url: String, body: JSONObject): String? = try {
        val c = open(url, "POST")
        c.doOutput = true
        c.setRequestProperty("Content-Type", "application/json")
        c.outputStream.use { os: OutputStream -> os.write(body.toString().toByteArray()) }
        if (c.responseCode in 200..299) {
            c.inputStream.bufferedReader().use(BufferedReader::readText)
        } else null
    } catch (e: Exception) { null }

    fun postBytes(url: String, field: String, filename: String, data: ByteArray, mime: String): Boolean = try {
        val boundary = "----signo" + System.currentTimeMillis()
        val c = open(url, "POST")
        c.doOutput = true
        c.setRequestProperty("Content-Type", "multipart/form-data; boundary=$boundary")
        c.outputStream.use { os ->
            val head = ("--$boundary\r\n"
                + "Content-Disposition: form-data; name=\"$field\"; filename=\"$filename\"\r\n"
                + "Content-Type: $mime\r\n\r\n").toByteArray()
            os.write(head)
            os.write(data)
            os.write("\r\n--$boundary--\r\n".toByteArray())
        }
        c.responseCode in 200..299
    } catch (e: Exception) { false }
}
