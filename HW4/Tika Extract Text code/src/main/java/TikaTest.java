import org.apache.tika.Tika;
import java.io.File;
import java.io.FileWriter;
import java.io.PrintWriter;

public class TikaTest {
    public static void main(String[] args) throws Exception {
        Tika tika = new Tika();
//        String filePath = "./HTML Files";
        String filePath = "C:\\Users\\rushk\\Desktop\\CS books\\CSCI 572\\HWs\\HW4\\shared\\solr-7.7.3\\example\\LATIMES\\latimes";
        File d = new File(filePath);
        File[] files = d.listFiles();

        File f = new File("big.txt");
        FileWriter fw = new FileWriter(f, true);
        PrintWriter pw = new PrintWriter(fw);

        for(File ff : files) {
            String text = tika.parseToString(ff);
            text = text.replaceAll("\\s+", " ");
            pw.println(text);
        }

        pw.flush();
        fw.flush();
        pw.close();
        fw.close();
    }
}